<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Transaction;

use SqlFtw\Sql\Names\TableName;
use SqlFtw\SqlFormatter\SqlFormatter;

class LockTablesItem implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\TableName */
    private $table;

    /** @var \SqlFtw\Sql\Dml\Transaction\LockTableType */
    private $lock;

    /** @var string|null */
    private $alias;

    public function __construct(TableName $table, LockTableType $lock, ?string $alias)
    {
        $this->table = $table;
        $this->lock = $lock;
        $this->alias = $alias;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = $this->table->serialize($formatter);
        if ($this->alias !== null) {
            $result .= ' AS ' . $formatter->formatName($this->alias);
        }
        $result .= ' ' . $this->lock->serialize($formatter);

        return $result;
    }

}
