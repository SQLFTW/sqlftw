<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets\Response;

use Dogma\InvalidValueException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Protocol\Mysql\Capability;
use SqlFtw\Protocol\Mysql\Packets\PacketData;
use SqlFtw\Protocol\Mysql\Packets\PacketHeader;
use SqlFtw\Protocol\Mysql\Packets\Response;
use SqlFtw\Protocol\Mysql\QueryResponse;
use SqlFtw\Protocol\Mysql\Status;

class Success implements Response, QueryResponse
{
    use StrictBehaviorMixin;

    /** @var int (0 for OK, 254 for EOF) */
    private $header;

    /** @var int */
    private $affectedRows;

    /** @var int */
    private $lastInsertId;

    /** @var int */
    private $status;

    /** @var int */
    private $warnings;

    /** @var string|null */
    private $info;

    /** @var string|null */
    private $sessionStateInfo;

    public function __construct(
        int $header,
        int $affectedRows,
        int $lastInsertId,
        int $status,
        int $warnings,
        ?string $info,
        ?string $sessionStateInfo
    ) {
        if ($header !== PacketHeader::OK && $header !== PacketHeader::EOF) {
            throw new InvalidValueException($header, '0 or 254');
        }

        $this->header = $header;
        $this->affectedRows = $affectedRows;
        $this->lastInsertId = $lastInsertId;
        $this->status = $status;
        $this->warnings = $warnings;
        $this->info = $info;
        $this->sessionStateInfo = $sessionStateInfo;
    }

    public static function createFromData(PacketData $data, int $capabilities): self
    {
        $type = $data->readUint8();
        $affectedRows = $data->readVarUint();
        $lastInsertId = $data->readVarUint();

        $status = $warnings = 0;
        if ($capabilities & Capability::PROTOCOL_41) {
            $status = $data->readUint16();
            $warnings = $data->readUint16();
        } elseif ($capabilities & Capability::TRANSACTIONS) {
            $status = $data->readUint16();
        }

        $sessionStateInfo = null;
        if ($capabilities & Capability::SESSION_TRACK) {
            $info = $data->readVarString();

            if ($status & Status::SESSION_STATE_CHANGED) {
                $sessionStateInfo = $data->readVarString();
            }
        } elseif (!$data->eof()) {
            $info = $data->readEofString();
        } else {
            $info = null;
        }
        $data->checkEof();

        return new self($type, $affectedRows, $lastInsertId, $status, $warnings, $info, $sessionStateInfo);
    }

    public function serialize(int $capabilities): PacketData
    {
        $data = new PacketData();
        $data->writeUint8($this->header);
        $data->writeVarUint($this->affectedRows);
        $data->writeVarUint($this->lastInsertId);

        if ($capabilities & Capability::PROTOCOL_41) {
            $data->writeUint16($this->status);
            $data->writeUint16($this->warnings);
        } elseif ($capabilities & Capability::TRANSACTIONS) {
            $data->writeUint16($this->status);
        }

        if ($capabilities & Capability::SESSION_TRACK) {
            $data->writeVarString($this->info);

            if ($this->status & Status::SESSION_STATE_CHANGED) {
                $data->writeVarString($this->sessionStateInfo);
            }
        } elseif ($this->info !== null) {
            $data->writeEofString($this->info);
        }

        return $data;
    }

    public function getHeader(): int
    {
        return $this->header;
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }

    public function getLastInsertId(): int
    {
        return $this->lastInsertId;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getWarnings(): int
    {
        return $this->warnings;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function getSessionStateInfo(): ?string
    {
        return $this->sessionStateInfo;
    }

}
