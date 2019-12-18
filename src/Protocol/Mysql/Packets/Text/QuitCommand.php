<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Text;

use Dogma\InvalidValueException;
use SqlFtw\Protocol\Mysql\Packets\Command;
use SqlFtw\Protocol\Mysql\Packets\PacketHeader;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use function chr;

class QuitCommand implements Command
{

    public function getHeader(): int
    {
        return PacketHeader::QUIT;
    }

    public static function createFromData(PacketData $data, int $capabilities): self
    {
        $header = $data->readUint8();
        if ($header !== PacketHeader::QUIT) {
            throw new InvalidValueException($header, (string) PacketHeader::QUIT);
        }

        $data->checkEof();

        return new self();
    }

    public function serialize(int $capabilities): PacketData
    {
        return new PacketData(chr(PacketHeader::QUIT));
    }

}
