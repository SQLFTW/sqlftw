<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Text;

use Dogma\InvalidValueException;
use SqlFtw\Protocol\Mysql\Packets\Command;
use SqlFtw\Protocol\Mysql\Packets\ErrorPacket;
use SqlFtw\Protocol\Mysql\Packets\OkPacket;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Protocol\Mysql\Packets\PacketHeader;

class ResetConnectionCommand implements Command
{

    public function getHeader(): int
    {
        return PacketHeader::RESET_CONNECTION;
    }

    public function returns(): array
    {
        return [
            OkPacket::class,
            ErrorPacket::class,
        ];
    }

    public static function createFromData(PacketData $data, int $capabilities): self
    {
        $header = $data->readUint8();
        if ($header !== PacketHeader::RESET_CONNECTION) {
            throw new InvalidValueException($header, (string) PacketHeader::RESET_CONNECTION);
        }

        $data->checkEof();

        return new self();
    }

    public function serialize(int $capabilities): PacketData
    {
        return new PacketData(chr(PacketHeader::RESET_CONNECTION));
    }

}
