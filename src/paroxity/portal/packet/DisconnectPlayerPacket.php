<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\Portal;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

/**
 * Sent by the proxy to a server to request that it disconnects any existing session for the specified
 * player. This is sent before transferring a player to ensure stale sessions are cleaned up.
 */
class DisconnectPlayerPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::DISCONNECT_PLAYER_PACKET;

    private string $playerName;

    public static function create(string $playerName): self
    {
        $result = new self;
        $result->playerName = $playerName;
        return $result;
    }

    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    protected function decodePayload(PacketSerializer $in): void
    {
        $this->playerName = $in->getString();
    }

    protected function encodePayload(PacketSerializer $out): void
    {
        $out->putString($this->playerName);
    }

    public function handlePacket(): void
    {
        Portal::getInstance()->handleDisconnectPlayer($this);
    }
}
