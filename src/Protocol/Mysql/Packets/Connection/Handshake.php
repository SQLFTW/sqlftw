<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Connection;

use Dogma\Char;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Protocol\Mysql\Packets\Packet;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Protocol\Mysql\UnknownServerProtocolVersionException;

class Handshake implements Packet
{
    use StrictBehaviorMixin;

    public const PROTOCOL_VERSION = 10;

    /** @var int */
    private $protocolVersion;

    /** @var string */
    private $serverVersion;

    /** @var int */
    private $connectionId;

    /** @var int|null */
    private $charsetId;

    /** @var int */
    private $capabilities;

    /** @var int|null */
    private $status;

    /** @var string */
    private $authData;

    /** @var string|null */
    private $authPluginName;

    /** @var \SqlFtw\Protocol\Mysql\Packets\PacketData|null */
    private $data;

    public function __construct(
        string $serverVersion,
        int $connectionId,
        ?int $charsetId,
        int $capabilities,
        ?int $status,
        string $authData,
        ?string $authPluginName
    ) {
        $this->protocolVersion = self::PROTOCOL_VERSION;
        $this->serverVersion = $serverVersion;
        $this->connectionId = $connectionId;
        $this->charsetId = $charsetId;
        $this->capabilities = $capabilities;
        $this->status = $status;
        $this->authData = $authData;
        $this->authPluginName = $authPluginName;
    }

    public static function createFromData(PacketData $data, int $capabilities = 0): self
    {
        $protocolVersion = $data->readUint8();
        if ($protocolVersion !== self::PROTOCOL_VERSION) {
            throw new UnknownServerProtocolVersionException($protocolVersion, self::PROTOCOL_VERSION);
        }

        $serverVersion = $data->readNulString();
        $connectionId = $data->readUint32();

        $authPluginData = $data->readFixedString(8);
        $data->skip(1);
        $capabilityFlags = $data->readUint16();

        $charsetId = $statusFlags = $authPluginName = null;
        if (!$data->eof()) {
            $charsetId = $data->readUint8();
            $statusFlags = $data->readUint16();
            $capabilityFlags += $data->readUint16() << 16;
            $length = $data->readUint8();
            $data->skip(10);
            $authPluginData .= $data->readFixedString($length - 8);
            $authPluginName = $data->readNulString();
        }

        $data->checkEof();

        $self = new self($serverVersion, $connectionId, $charsetId, $capabilityFlags, $statusFlags, $authPluginData, $authPluginName);
        $self->data = $data;

        return $self;
    }

    public function serialize(int $capabilities = 0): PacketData
    {
        $data = new PacketData();
        $data->writeUint8($this->protocolVersion);
        $data->writeNulString($this->serverVersion);
        $data->writeUint32($this->connectionId);
        $data->writeFixedString(substr($this->authData, 0, 8), 8);
        $data->fill(Char::NULL, 1);
        $data->writeUint16($this->capabilities & 0xFFFF);
        if ($this->charsetId !== null || $this->status !== null || $this->authPluginName !== null || strlen($this->authData) > 8) {
            $data->writeUint8($this->charsetId);
            $data->writeUint16($this->status);
            $data->writeUint16($this->capabilities >> 16);
            $data->writeUint8(strlen($this->authData));
            $data->fill(Char::NUL, 10);
            $data->writeFixedString(substr($this->authData, 8), strlen($this->authData) - 8);
            $data->writeNulString($this->authPluginName);
        }

        return $data;
    }

    public function getProtocolVersion(): int
    {
        return $this->protocolVersion;
    }

    public function getServerVersion(): string
    {
        return $this->serverVersion;
    }

    public function getConnectionId(): int
    {
        return $this->connectionId;
    }

    public function getCharsetId(): int
    {
        return $this->charsetId;
    }

    public function getCapabilities(): int
    {
        return $this->capabilities;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getAuthData(): string
    {
        return $this->authData;
    }

    public function getAuthPluginName(): string
    {
        return $this->authPluginName;
    }

    public function getData(): PacketData
    {
        return $this->data;
    }

}