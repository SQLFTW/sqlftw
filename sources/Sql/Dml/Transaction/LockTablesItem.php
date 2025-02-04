<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Transaction;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Node;

class LockTablesItem extends Node
{

    public ObjectIdentifier $table;

    public LockTableType $lock;

    public ?string $alias;

    public function __construct(ObjectIdentifier $table, LockTableType $lock, ?string $alias)
    {
        $this->table = $table;
        $this->lock = $lock;
        $this->alias = $alias;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->table->serialize($formatter);
        if ($this->alias !== null) {
            $result .= ' AS ' . $formatter->formatName($this->alias);
        }
        $result .= ' ' . $this->lock->serialize($formatter);

        return $result;
    }

}
