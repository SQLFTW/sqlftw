<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Response;

use SqlFtw\Protocol\Mysql\Packets\Packet;
use SqlFtw\Protocol\Mysql\Packets\PacketData;

class ColumnDefinition implements Packet
{

    /** @var string */
    private $catalog;

    /** @var string */
    private $schema;

    /** @var string */
    private $table;

    /** @var string */
    private $originalTable;

    /** @var string */
    private $name;

    /** @var string */
    private $originalName;

    /** @var int */
    private $charsetId;

    /** @var int */
    private $length;

    /** @var int */
    private $type;

    /** @var int */
    private $flags;

    /** @var int */
    private $decimals;

    /** @var string|null */
    private $default;

    public function __construct(
        string $catalog,
        string $schema,
        string $table,
        string $originalTable,
        string $name,
        string $originalName,
        int $charsetId,
        int $length,
        int $type,
        int $flags,
        int $decimals,
        ?string $default = null
    ) {
        $this->catalog = $catalog;
        $this->schema = $schema;
        $this->table = $table;
        $this->originalTable = $originalTable;
        $this->name = $name;
        $this->originalName = $originalName;
        $this->charsetId = $charsetId;
        $this->length = $length;
        $this->type = $type;
        $this->flags = $flags;
        $this->decimals = $decimals;
        $this->default = $default;
    }

    public static function createFromData(PacketData $data, int $capabilities)
    {
        $catalog = $data->readVarString();
        $schema = $data->readVarString();
        $table = $data->readVarString();
        $originalTable = $data->readVarString();
        $name = $data->readVarString();
        $originalName = $data->readVarString();
        $charsetId = $data->readUint16();
        $length = $data->readUint32();
        $type = $data->readUint8();
        $flags = $data->readUint16();
        $decimals = $data->readUint8();
        $data->skip(2);

        $default = null;
        if (!$data->eof()) {
            $default = $data->readVarString();
        }

        return new self($catalog, $schema, $table, $originalTable, $name, $originalName, $charsetId, $length, $type, $flags, $decimals, $default);
    }

    public function serialize(int $capabilities): PacketData
    {
        $data = new PacketData();
        $data->writeVarString($this->catalog);
        $data->writeVarString($this->schema);
        $data->writeVarString($this->table);
        $data->writeVarString($this->originalTable);
        $data->writeVarString($this->name);
        $data->writeVarString($this->originalName);
        $data->writeUint16($this->charsetId);
        $data->writeUint32($this->length);
        $data->writeUint8($this->type);
        $data->writeUint16($this->flags);
        $data->writeUint8($this->decimals);
        $data->fill("0x00", 2);

        if ($this->default !== null) {
            $data->writeVarString($this->default);
        }

        return $data;
    }

    public function getCatalog(): string
    {
        return $this->catalog;
    }

    public function getSchema(): string
    {
        return $this->schema;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getOriginalTable(): string
    {
        return $this->originalTable;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getCharsetId(): int
    {
        return $this->charsetId;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    /**
     * @return string|null
     * @deprecated in response to a deprecated COM_FIELD_LIST
     */
    public function getDefault(): ?string
    {
        return $this->default;
    }

}
