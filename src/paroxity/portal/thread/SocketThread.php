<?php
declare(strict_types=1);

namespace paroxity\portal\thread;

use Exception;
use paroxity\portal\packet\AuthRequestPacket;
use paroxity\portal\packet\Packet;
use paroxity\portal\packet\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\Thread;
use pocketmine\utils\Binary;
use pmmp\thread\ThreadSafeArray;
use Socket;
use function sleep;
use function socket_close;
use function socket_connect;
use function socket_create;
use function socket_last_error;
use function socket_read;
use function socket_set_nonblock;
use function socket_write;
use function strlen;
use function usleep;
use const AF_INET;
use const SOCK_STREAM;
use const SOL_TCP;

class SocketThread extends Thread
{
    private string $host;
    private int $port;

    private string $secret;
    private string $name;

    private ThreadSafeArray $sendQueue;
    private ThreadSafeArray $receiveBuffer;

    private SleeperHandlerEntry $sleeperEntry;

    private bool $isRunning;

    public function __construct(string $host, int $port, string $secret, string $name, SleeperHandlerEntry $sleeperEntry)
    {
        $this->host = $host;
        $this->port = $port;

        $this->secret = $secret;

        $this->name = $name;

        $this->sendQueue = new ThreadSafeArray();
        $this->receiveBuffer = new ThreadSafeArray();

        $this->sleeperEntry = $sleeperEntry;

        $this->isRunning = true;
        $this->start();
    }

    public function onRun(): void
    {
        $this->registerClassLoaders();
        $notifier = $this->sleeperEntry->createNotifier();

        $socket = $this->connectToSocketServer();
		if($socket === null) {
			return;
		}

        while ($socket !== null && $this->isRunning) {
            while (($send = $this->sendQueue->shift()) !== null) {
                $length = strlen($send);
                $wrote = @socket_write($socket, Binary::writeLInt($length) . $send, 4 + $length);
                if ($wrote !== 4 + $length) {
                    socket_close($socket);
                    $socket = $this->connectToSocketServer();
                    if($socket === null) {
                        return;
                    }
                }
            }

            do {
                $read = @socket_read($socket, 4);
                if(!$read && socket_last_error($socket) === 10054) {
                    socket_close($socket);
                    $socket = $this->connectToSocketServer();
                    if($socket === null) {
	                    return;
                    }
                }
                if($read !== false) {
                    if (strlen($read) === 4) {
                        $length = Binary::readLInt($read);
                        $read = @socket_read($socket, $length);
                        if ($read !== false) {
                            $this->receiveBuffer[] = $read;
                            $notifier->wakeupSleeper();
                        }
                    } elseif ($read === "") {
                        socket_close($socket);
                        $socket = $this->connectToSocketServer();
                        if($socket === null) {
	                        return;
                        }
                    }
                }
            } while ($read !== false);
            usleep(25000);
        }
	    socket_close($socket);
    }

    public function connectToSocketServer(): ?Socket
    {
        do {
            if(!$this->isRunning) {
                return null;
            }
            $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        } while (!$socket);

        do {
            if(!$this->isRunning) {
                return null;
            }
            $connected = @socket_connect($socket, $this->host, $this->port);
            if (!$connected) {
                sleep(5);
            }
        } while (!$connected);
        socket_set_nonblock($socket);

        $pk = AuthRequestPacket::create(ProtocolInfo::PROTOCOL_VERSION, $this->secret, $this->name);
        $this->addPacketToQueue($pk);

        return $socket;
    }

    public function quit(): void
    {
        $this->isRunning = false;
        parent::quit();
    }

    public function addPacketToQueue(Packet $packet): void
    {
        $serializer = PacketSerializer::encoder();
    	$packet->encode($serializer);
    	$this->sendQueue[] = $serializer->getBuffer();
    }

    public function getBuffer(): ?string
    {
        return $this->receiveBuffer->shift();
    }
}
