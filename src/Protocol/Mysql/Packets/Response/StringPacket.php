<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Response;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Protocol\Mysql\Packets\Packet;
use SqlFtw\Protocol\Mysql\Packets\PacketData;

class StringPacket implements Packet
{
    use StrictBehaviorMixin;

    /** @var string */
    private $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public static function createFromData(PacketData $data, int $capabilities): self
    {
        $string = $data->readEofString();

        return new self($string);
    }

    public function serialize(int $capabilities): PacketData
    {
        $data = new PacketData();
        $data->writeEofString($this->string);

        return $data;
    }

    public function getString(): string
    {
        return $this->string;
    }

}
