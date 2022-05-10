<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Replication;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlSerializable;

class ReplicationGtidAssignOption implements SqlSerializable
{

    public const OFF = Keyword::OFF;
    public const LOCAL = Keyword::LOCAL;
    public const UUID = 'UUID';

    /** @var 'OFF'|'LOCAL'|'UUID' */
    private $type;

    /** @var string|null */
    private $uuid;

    /**
     * @param 'OFF'|'LOCAL'|'UUID' $type
     * @param string|null $uuid
     */
    public function __construct(string $type, ?string $uuid = null)
    {
        $this->type = $type;
        $this->uuid = $uuid;
    }

    /**
     * @return 'OFF'|'LOCAL'|'UUID'
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->type === self::UUID ? $formatter->formatString($this->uuid) : $this->type;
    }

}
