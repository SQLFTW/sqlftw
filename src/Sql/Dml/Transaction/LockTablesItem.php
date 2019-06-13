<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Transaction;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\SqlSerializable;

class LockTablesItem implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName */
    private $table;

    /** @var \SqlFtw\Sql\Dml\Transaction\LockTableType|null */
    private $lock;

    /** @var string|null */
    private $alias;

    public function __construct(QualifiedName $table, ?LockTableType $lock, ?string $alias)
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
        if ($this->lock !== null) {
            $result .= ' ' . $this->lock->serialize($formatter);
        }

        return $result;
    }

}
