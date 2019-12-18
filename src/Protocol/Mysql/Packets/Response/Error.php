<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Response;

use Dogma\InvalidValueException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Protocol\Mysql\Capability;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Protocol\Mysql\Packets\PacketHeader;
use SqlFtw\Protocol\Mysql\Packets\Response;
use SqlFtw\Protocol\Mysql\QueryResponse;

class Error implements Response, QueryResponse
{
    use StrictBehaviorMixin;

    /** @var int */
    private $errorCode;

    /** @var string */
    private $errorMessage;

    /** @var string|null */
    private $sqlState;

    public function __construct(
        int $errorCode,
        string $errorMessage,
        ?string $sqlState
    ) {
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->sqlState = $sqlState;
    }

    public static function createFromData(PacketData $data, int $capabilities)
    {
        $header = $data->readUint8();
        if ($header !== PacketHeader::ERROR) {
            throw new InvalidValueException($header, '255');
        }

        $errorCode = $data->readUint16();

        $sqlState = null;
        if ($capabilities & Capability::PROTOCOL_41) {
            $data->readFixedString(1); // marker #
            $sqlState = $data->readFixedString(5);
        }

        $errorMessage = $data->readEofString();

        $data->checkEof();

        return new self($errorCode, $errorMessage, $sqlState);
    }

    public function serialize(int $capabilities): PacketData
    {
        $data = new PacketData();
        $data->writeUint8(PacketHeader::ERROR);
        $data->writeUint16($this->errorCode);

        if ($capabilities & Capability::PROTOCOL_41) {
            $data->writeFixedString('#' . $this->sqlState, 6);
        }

        $data->writeEofString($this->errorMessage);

        return $data;
    }

    public function getHeader(): int
    {
        return PacketHeader::ERROR;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function getSqlState(): string
    {
        return $this->sqlState;
    }

}
