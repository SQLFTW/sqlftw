<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Text;

use Dogma\InvalidValueException;
use SqlFtw\Protocol\Mysql\Packets\Command;
use SqlFtw\Protocol\Mysql\Packets\OkPacket;
use SqlFtw\Protocol\Mysql\Packets\PacketHeader;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Protocol\Mysql\Packets\Response\Error;

class InitDatabaseCommand implements Command
{

    /** @var string */
    private $database;

    public function __construct(string $database)
    {
        $this->database = $database;
    }

    public function getHeader(): int
    {
        return PacketHeader::INIT_DATABASE;
    }

    public function returns(): array
    {
        return [
            OkPacket::class,
            Error::class,
        ];
    }

    public static function createFromData(PacketData $data, int $capabilities): self
    {
        $code = $data->readUint8();
        if ($code !== PacketHeader::INIT_DATABASE) {
            throw new InvalidValueException($code, (string) PacketHeader::INIT_DATABASE);
        }

        $database = $data->readEofString();

        return new self($database);
    }

    public function serialize(int $capabilities): PacketData
    {
        $data = new PacketData();
        $data->writeUint8(PacketHeader::INIT_DATABASE);
        $data->writeEofString($this->database);

        return $data;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

}
