<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\SqlSerializable;

class SelectLocking implements SqlSerializable
{

    public SelectLockOption $for;

    public ?SelectLockWaitOption $wait;

    /** @var non-empty-list<ObjectIdentifier>|null */
    public ?array $tables;

    /**
     * @param non-empty-list<ObjectIdentifier>|null $tables
     */
    public function __construct(SelectLockOption $for, ?SelectLockWaitOption $wait = null, ?array $tables = null)
    {
        $this->for = $for;
        $this->wait = $wait;
        $this->tables = $tables;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->for->serialize($formatter);
        if ($this->tables !== null) {
            $result .= ' OF ' . $formatter->formatSerializablesList($this->tables);
        }
        if ($this->wait !== null) {
            $result .= ' ' . $this->wait->serialize($formatter);
        }

        return $result;
    }

}
