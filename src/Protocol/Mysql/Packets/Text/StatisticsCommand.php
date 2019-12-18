<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Text;

use Dogma\InvalidValueException;
use SqlFtw\Protocol\Mysql\Packets\Command;
use SqlFtw\Protocol\Mysql\Packets\OkPacket;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Protocol\Mysql\Packets\PacketHeader;
use SqlFtw\Protocol\Mysql\Packets\StringPacket;

class StatisticsCommand implements Command
{

    public function getHeader(): int
    {
        return PacketHeader::STATISTICS;
    }

    public function returns(): array
    {
        return [
            StringPacket::class,
        ];
    }

    public static function createFromData(PacketData $data, int $capabilities): self
    {
        $header = $data->readUint8();
        if ($header !== PacketHeader::STATISTICS) {
            throw new InvalidValueException($header, (string) PacketHeader::STATISTICS);
        }

        $data->checkEof();

        return new self();
    }

    public function serialize(int $capabilities): PacketData
    {
        return new PacketData(chr(PacketHeader::STATISTICS));
    }

}
