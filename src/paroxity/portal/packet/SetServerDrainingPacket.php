<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

/**
 * Sent by a server to tell the proxy whether load balancers should stop routing new players to it.
 * Players already connected to the server are unaffected. Typically sent before a planned restart or
 * deployment so the server can finish serving its current players before going down.
 */
class SetServerDrainingPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::SET_SERVER_DRAINING_PACKET;

    private bool $draining;

    public static function create(bool $draining): self
    {
        $result = new self;
        $result->draining = $draining;
        return $result;
    }

    public function isDraining(): bool
    {
        return $this->draining;
    }

    protected function decodePayload(PacketSerializer $in): void
    {
        $this->draining = $in->getBool();
    }

    protected function encodePayload(PacketSerializer $out): void
    {
        $out->putBool($this->draining);
    }

    public function handlePacket(): void
    {
        // NOOP — this proxy never sends this packet back to servers.
    }
}
