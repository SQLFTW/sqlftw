<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Text;

use Dogma\InvalidValueException;
use SqlFtw\Protocol\Mysql\Packets\Command;
use SqlFtw\Protocol\Mysql\Packets\OkPacket;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Protocol\Mysql\Packets\PacketHeader;
use SqlFtw\Protocol\Mysql\Packets\Response\Error;
use SqlFtw\Protocol\Mysql\Packets\Response\IntPacket;
use SqlFtw\Protocol\Mysql\Packets\Response\LocalInfileRequest;

class QueryCommand implements Command
{

    /** @var string */
    private $query;

    public function __construct(string $query)
    {
        $this->query = $query;
    }

    public function getHeader(): int
    {
        return PacketHeader::QUERY;
    }

    public function returns(): array
    {
        return [
            OkPacket::class,
            Error::class,
            LocalInfileRequest::class,
            'int', // column count
        ];
    }

    public static function createFromData(PacketData $data, int $capabilities): self
    {
        $code = $data->readUint8();
        if ($code !== PacketHeader::QUERY) {
            throw new InvalidValueException($code, (string) PacketHeader::QUERY);
        }

        $database = $data->readEofString();

        return new self($database);
    }

    public function serialize(int $capabilities): PacketData
    {
        $data = new PacketData();
        $data->writeUint8(PacketHeader::QUERY);
        $data->writeEofString($this->query);

        return $data;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

}
