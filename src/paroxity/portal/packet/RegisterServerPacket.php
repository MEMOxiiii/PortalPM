<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class RegisterServerPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::REGISTER_SERVER_PACKET;

	private string $address;
	private bool $legacyAuth;

    public static function create(string $address, bool $legacyAuth = true): self
    {
        $result = new self;
        $result->address = $address;
        $result->legacyAuth = $legacyAuth;
        return $result;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function isLegacyAuth(): bool
    {
        return $this->legacyAuth;
    }

    protected function decodePayload(PacketSerializer $in): void
    {
        $this->address = $in->getString();
        $this->legacyAuth = $in->getBool();
    }

    protected function encodePayload(PacketSerializer $out): void
    {
        $out->putString($this->address);
        $out->putBool($this->legacyAuth);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}
