<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Connection;

use Dogma\Char;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Protocol\Mysql\Capability;
use SqlFtw\Protocol\Mysql\Packets\Packet;
use SqlFtw\Protocol\Mysql\Packets\PacketData;

class HandshakeResponse implements Packet
{
    use StrictBehaviorMixin;

    /** @var int */
    private $capabilities;

    /** @var int */
    private $maxPacketSize;

    /** @var int */
    private $charsetId;

    /** @var string */
    private $userName;

    /** @var string|null */
    private $database;

    /** @var string */
    private $authResponse;

    /** @var string|null */
    private $authPluginName;

    /** @var mixed[] */
    private $connectionAttributes;

    /** @var \SqlFtw\Protocol\Mysql\Packets\PacketData|null */
    private $data;

    public function __construct(
        int $capabilityFlags,
        int $maxPacketSize,
        int $charsetId,
        string $userName,
        ?string $database,
        string $authResponse,
        ?string $authPluginName,
        array $connectionAttributes = []
    )
    {
        $this->capabilities = $capabilityFlags;
        $this->maxPacketSize = $maxPacketSize;
        $this->charsetId = $charsetId;
        $this->userName = $userName;
        $this->database = $database;
        $this->authResponse = $authResponse;
        $this->authPluginName = $authPluginName;
        $this->connectionAttributes = $connectionAttributes;
    }

    public static function createFromData(PacketData $data, int $capabilities = 0)
    {
        $capabilityFlags = $data->readUint32();
        $maxPacketSize = $data->readUint32();
        $charsetId = $data->readUint8();
        $data->skip(23);
        $userName = $data->readNulString();

        if ($capabilityFlags & Capability::PLUGIN_AUTH_LENENC_CLIENT_DATA) {
            $authResponse = $data->readVarString();
        } elseif ($capabilityFlags & Capability::SECURE_CONNECTION) {
            $length = $data->readUint8();
            $authResponse = $data->readFixedString($length);
        } else {
            $authResponse = $data->readNulString();
        }

        $database = null;
        if ($capabilityFlags & Capability::CONNECT_WITH_DATABASE) {
            $database = $data->readNulString();
        }

        $authPluginName = null;
        if ($capabilityFlags & Capability::PLUGIN_AUTH) {
            $authPluginName = $data->readNulString();
        }

        $connectionAttributes = [];
        if ($capabilityFlags & Capability::CONNECT_ATTRS) {
            $connectionAttributes = $data->readMap();
        }

        $data->checkEof();

        $self = new self($capabilityFlags, $maxPacketSize, $charsetId, $userName, $database, $authResponse, $authPluginName, $connectionAttributes);
        $self->data = $data;

        return $self;
    }

    public function serialize(int $capabilities = 0): PacketData
    {
        $data = new PacketData();
        $data->writeUint32($this->capabilities);
        $data->writeUint32($this->maxPacketSize);
        $data->writeUint8($this->charsetId);
        $data->fill(Char::NUL, 23);
        $data->writeNulString($this->userName);

        if ($this->capabilities & Capability::PLUGIN_AUTH_LENENC_CLIENT_DATA) {
            $data->writeVarString($this->authResponse);
        } elseif ($this->capabilities & Capability::SECURE_CONNECTION) {
            $data->writeUint8(strlen($this->authResponse));
            $data->writeFixedString($this->authResponse, strlen($this->authResponse));
        } else {
            $data->writeNulString($this->authResponse);
        }

        if ($this->capabilities & Capability::CONNECT_WITH_DATABASE) {
            $data->writeNulString($this->database);
        }

        if ($this->capabilities & Capability::PLUGIN_AUTH) {
            $data->writeNulString($this->authPluginName);
        }

        if ($this->capabilities & Capability::CONNECT_ATTRS) {
            $data->writeMap($this->connectionAttributes);
        }

        return $data;
    }

    public function getCapabilities(): int
    {
        return $this->capabilities;
    }

    public function getMaxPacketSize(): int
    {
        return $this->maxPacketSize;
    }

    public function getCharsetId(): int
    {
        return $this->charsetId;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function getAuthResponse(): string
    {
        return $this->authResponse;
    }

    public function getAuthPluginName(): ?string
    {
        return $this->authPluginName;
    }

    public function getConnectionAttributes(): array
    {
        return $this->connectionAttributes;
    }

}