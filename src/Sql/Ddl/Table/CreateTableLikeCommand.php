<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\TableName;

class CreateTableLikeCommand implements \SqlFtw\Sql\Ddl\Table\AnyCreateTableCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\TableName */
    private $table;

    /** @var \SqlFtw\Sql\TableName */
    private $oldTable;

    /** @var bool */
    private $temporary;

    /** @var bool */
    private $ifNotExists;

    public function __construct(TableName $table, TableName $oldTable, bool $temporary = false, bool $ifNotExists = false)
    {
        $this->table = $table;
        $this->oldTable = $oldTable;
        $this->temporary = $temporary;
        $this->ifNotExists = $ifNotExists;
    }

    public function getTable(): TableName
    {
        return $this->table;
    }

    public function getOldTable(): TableName
    {
        return $this->oldTable;
    }

    public function isTemporary(): bool
    {
        return $this->temporary;
    }

    public function ifNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE ';
        if ($this->temporary) {
            $result .= 'TEMPORARY ';
        }
        $result .= 'TABLE ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS ';
        }
        $result .= $this->table->serialize($formatter);

        $result .= ' LIKE ' . $this->oldTable->serialize($formatter);

        return $result;
    }

}
