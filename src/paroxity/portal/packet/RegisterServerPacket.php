<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class RegisterServerPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::REGISTER_SERVER_PACKET;

	private string $address;
	private bool $legacyAuth;
	private string $group;
	private int $weight;

    public static function create(string $address, bool $legacyAuth = true, string $group = "", int $weight = 0): self
    {
        $result = new self;
        $result->address = $address;
        $result->legacyAuth = $legacyAuth;
        $result->group = $group;
        $result->weight = $weight;
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

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    protected function decodePayload(PacketSerializer $in): void
    {
        $this->address = $in->getString();
        $this->legacyAuth = $in->getBool();
        $this->group = $in->getString();
        $this->weight = $in->getUnsignedVarInt();
    }

    protected function encodePayload(PacketSerializer $out): void
    {
        $out->putString($this->address);
        $out->putBool($this->legacyAuth);
        $out->putString($this->group);
        $out->putUnsignedVarInt($this->weight);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}
