<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets;

use Dogma\Char;
use Dogma\Check;
use Dogma\Equalable;
use Dogma\Pack;
use Dogma\ShouldNotHappenException;
use Dogma\StaticClassMixin;
use SqlFtw\Protocol\Mysql\UnparsedDataException;
use function hex2bin;
use function ord;
use function str_replace;

class PacketData implements Equalable
{
    use StaticClassMixin;

    /** @var string */
    private $data;

    /** @var int */
    private $position;

    /** @var bool */
    private $locked = false;

    public function __construct(string $data = '')
    {
        $this->data = $data;
        $this->position = 0;
    }

    public static function fromHex(string $hex): self
    {
        $self = new self(hex2bin(str_replace([" ", "\t", "\r", "\n"], '', $hex)));
        $self->locked = true;

        return $self;
    }

    /**
     * @param \SqlFtw\Protocol\Mysql\Packets\PacketData $other
     * @return bool
     */
    public function equals(Equalable $other): bool
    {
        Check::instance($other, self::class);

        return $this->data === $other->data;
    }

    public function resetPosition(): void
    {
        $this->position = 0;
    }

    public function eof(): bool
    {
        return strlen($this->data) <= $this->position;
    }

    /**
     * @throws \SqlFtw\Protocol\Mysql\UnparsedDataException
     */
    public function checkEof(): void
    {
        if (!$this->eof()) {
            throw new UnparsedDataException(strlen($this->data) - $this->position);
        }
    }

    /**
     * @throws \Dogma\ShouldNotHappenException
     */
    private function checkLocked(): void
    {
        if ($this->locked) {
            throw new ShouldNotHappenException('PacketData are locked for writes. Either have been created from incoming packet or have been locked with writeEofString().');
        }
    }

    public function skip(int $length): void
    {
        $this->position += $length;
    }

    public function fill(string $char, int $length): void
    {
        $this->checkLocked();
        $this->data .= str_repeat($char, $length);
        $this->position += $length;
    }

    public function readUint8(): int
    {
        return ord($this->data[$this->position++]);
    }

    public function writeUint8(int $value): void
    {
        Check::intBounds($value, 8, false);
        $this->checkLocked();

        $this->data .= chr($value);
        $this->position++;
    }

    public function readUint16(): int
    {
        $i2 = ord($this->data[$this->position++]);
        $i1 = ord($this->data[$this->position++]);

        return ($i1 << 8) + $i2;
    }

    public function writeUint16(int $value): void
    {
        Check::intBounds($value, 16, false);
        $this->checkLocked();

        $this->data .= pack(Pack::UINT16_LE, $value);
        $this->position += 2;
    }

    public function readUint24(): int
    {
        $i3 = ord($this->data[$this->position++]);
        $i2 = ord($this->data[$this->position++]);
        $i1 = ord($this->data[$this->position++]);

        return ($i1 << 16) + ($i2 << 8) + $i3;
    }

    public function writeUint24(int $value): void
    {
        Check::intBounds($value, 24, false);
        $this->checkLocked();

        $this->data .= substr(pack(Pack::UINT32_LE, $value), 0, 3);
        $this->position += 3;
    }

    public function readUint32(): int
    {
        $i4 = ord($this->data[$this->position++]);
        $i3 = ord($this->data[$this->position++]);
        $i2 = ord($this->data[$this->position++]);
        $i1 = ord($this->data[$this->position++]);

        return ($i1 << 24) + ($i2 << 16) + ($i3 << 8) + $i4;
    }

    public function writeUint32(int $value): void
    {
        Check::intBounds($value, 32, false);
        $this->checkLocked();

        $this->data .= pack(Pack::UINT32_LE, $value);
        $this->position += 4;
    }

    public function readUint48(): int
    {
        $i6 = ord($this->data[$this->position++]);
        $i5 = ord($this->data[$this->position++]);
        $i4 = ord($this->data[$this->position++]);
        $i3 = ord($this->data[$this->position++]);
        $i2 = ord($this->data[$this->position++]);
        $i1 = ord($this->data[$this->position++]);

        return ($i1 << 40) + ($i2 << 32) + ($i3 << 24) + ($i4 << 16) + ($i5 << 8) + $i6;
    }

    public function writeUint48(int $value): void
    {
        Check::intBounds($value, 48, false);
        $this->checkLocked();

        $this->data .= substr(pack(Pack::UINT64_LE, $value), 0, 6);
        $this->position += 6;
    }

    public function readUint64(): int
    {
        $i8 = ord($this->data[$this->position++]);
        $i7 = ord($this->data[$this->position++]);
        $i6 = ord($this->data[$this->position++]);
        $i5 = ord($this->data[$this->position++]);
        $i4 = ord($this->data[$this->position++]);
        $i3 = ord($this->data[$this->position++]);
        $i2 = ord($this->data[$this->position++]);
        $i1 = ord($this->data[$this->position++]);

        return ($i1 << 56) + ($i2 << 48) + ($i3 << 40) + ($i4 << 32) + ($i5 << 24) + ($i6 << 16) + ($i7 << 8) + $i8;
    }

    public function writeUint64(int $value): void
    {
        Check::intBounds($value, 64, false);
        $this->checkLocked();

        $this->data .= pack(Pack::UINT64_LE, $value);
        $this->position += 8;
    }

    public function readVarUint(bool $nullable = false): ?int
    {
        $value = $this->data[$this->position++];
        switch ($value) {
            case 0xfe:
                return self::readUint64();
            case 0xfd:
                return self::readUint24();
            case 0xfc:
                return self::readUint16();
            case 0xfb:
                if ($nullable) {
                    return null;
                }
            default:
                return $value;
        }
    }

    public function writeVarUint(int $value, bool $nullable = false): void
    {
        $this->checkLocked();

        if ($value >= 2**24) {
            $this->data .= 0xfe . pack(Pack::UINT64_LE, $value);
            $this->position += 9;
        } elseif ($value >= 2**16) {
            $this->data .= 0xfd . substr(pack(Pack::UINT32_LE, $value), 0, 3);
            $this->position += 4;
        } elseif ($value === 251 && $nullable) {
            $this->data .= 0xfb;
            $this->position++;
        } elseif ($value >= 251) {
            $this->data .= 0xfc . pack(Pack::UINT16_LE, $value);
            $this->position += 3;
        } else {
            $this->data .= chr($value);
            $this->position++;
        }
    }

    public function readVarString(): string
    {
        $length = $this->readVarUint();

        return $this->readFixedString($length);
    }

    public function writeVarString(string $string): void
    {
        $this->checkLocked();

        $this->writeVarUint(strlen($string));

        $this->writeFixedString($string, strlen($string));
    }

    public function readFixedString(int $length): string
    {
        $string = substr($this->data, $this->position, $length);
        $this->position += $length;

        return $string;
    }

    public function writeFixedString(string $string, int $length): void
    {
        Check::range(strlen($string), $length, $length);
        $this->checkLocked();

        $this->data .= $string;
        $this->position += $length;
    }

    public function readNulString(): string
    {
        $string = '';
        $char = $this->data[$this->position++];
        while ($char !== Char::NUL) {
            $string .= $char;
            $char = $this->data[$this->position++];
        }

        return $string;
    }

    public function writeNulString(string $string): void
    {
        $this->checkLocked();

        $this->data .= $string . Char::NUL;
        $this->position += strlen($string) + 1;
    }

    public function readEofString(): string
    {
        $string = substr($this->data, $this->position);
        $this->position += strlen($string);

        return $string;
    }

    public function writeEofString(string $string): void
    {
        $this->checkLocked();

        $this->data .= $string;
        $this->position += strlen($string);
        $this->locked = true;
    }

    public function readMap(): array
    {
        $length = $this->readVarUint();
        $position = $this->position;
        $items = [];
        while ($this->position < $position + $length) {
            $key = $this->readVarString();
            $value = $this->readVarString();
            $items[$key] = $value;
        }

        return $items;
    }

    /**
     * @param string[] $items
     */
    public function writeMap(array $items): void
    {
        $this->checkLocked();

        $position = $this->position;
        foreach ($items as $key => $value) {
            $this->writeVarString($key);
            $this->writeVarString($value);
        }
        $length = $this->position - $position;
        $map = substr($this->data, $position);
        $this->data = substr($this->data, 0, $position);
        $this->position = $position;
        $this->writeVarUint($length);
        $this->data .= $map;
        $this->position += $length;
    }

}