<?php
declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\serializer;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function ord;
use function pack;
use function strlen;
use function substr;
use function unpack;

/**
 * Compatibility serializer for plugins that rely on the old PacketSerializer API.
 * This implements only the methods needed by PortalPM packet encoding/decoding.
 */
class PacketSerializer
{
    private string $buffer;
    private int $offset;

    private function __construct(string $buffer = "", int $offset = 0)
    {
        $this->buffer = $buffer;
        $this->offset = $offset;
    }

    public static function encoder(): self
    {
        return new self();
    }

    public static function decoder(string $buffer, int $offset = 0): self
    {
        return new self($buffer, $offset);
    }

    public function rewind(): void
    {
        $this->offset = 0;
    }

    public function getBuffer(): string
    {
        return $this->buffer;
    }

    public function getRemaining(): string
    {
        $remaining = substr($this->buffer, $this->offset);
        $this->offset = strlen($this->buffer);
        return $remaining;
    }

    public function getBool(): bool
    {
        return $this->getByte() !== 0;
    }

    public function putBool(bool $value): void
    {
        $this->buffer .= $value ? "\x01" : "\x00";
    }

    public function getByte(): int
    {
        return ord($this->read(1));
    }

    public function putByte(int $value): void
    {
        $this->buffer .= chr($value & 0xff);
    }

    public function getLShort(): int
    {
        $value = unpack("v", $this->read(2));
        return $value[1];
    }

    public function putLShort(int $value): void
    {
        $this->buffer .= pack("v", $value);
    }

    public function getLInt(): int
    {
        $value = unpack("V", $this->read(4));
        return $value[1];
    }

    public function putLInt(int $value): void
    {
        $this->buffer .= pack("V", $value);
    }

    public function getLLong(): int
    {
        $value = unpack("P", $this->read(8));
        return (int) $value[1];
    }

    public function putLLong(int $value): void
    {
        $this->buffer .= pack("P", $value);
    }

    public function getUUID(): UuidInterface
    {
        return Uuid::fromBytes($this->read(16));
    }

    public function putUUID(UuidInterface $uuid): void
    {
        $this->buffer .= $uuid->getBytes();
    }

    public function getString(): string
    {
        $length = $this->getUnsignedVarInt();
        return $this->read($length);
    }

    public function putString(string $value): void
    {
        $this->putUnsignedVarInt(strlen($value));
        $this->buffer .= $value;
    }

    private function read(int $length): string
    {
        $value = substr($this->buffer, $this->offset, $length);
        $this->offset += strlen($value);
        return $value;
    }

    private function getUnsignedVarInt(): int
    {
        $value = 0;
        $shift = 0;

        while(true) {
            $byte = $this->getByte();
            $value |= (($byte & 0x7f) << $shift);
            if(($byte & 0x80) === 0) {
                return $value;
            }
            $shift += 7;
        }
    }

    private function putUnsignedVarInt(int $value): void
    {
        while(($value & ~0x7f) !== 0) {
            $this->buffer .= chr(($value & 0x7f) | 0x80);
            $value >>= 7;
        }

        $this->buffer .= chr($value);
    }
}
