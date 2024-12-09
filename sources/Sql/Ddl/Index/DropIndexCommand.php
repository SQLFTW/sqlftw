<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Index;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableAlgorithm;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableLock;
use SqlFtw\Sql\Ddl\Table\DdlTableCommand;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\StatementImpl;

class DropIndexCommand extends StatementImpl implements IndexCommand, DdlTableCommand
{

    public string $index;

    public ObjectIdentifier $table;

    public ?AlterTableAlgorithm $algorithm;

    public ?AlterTableLock $lock;

    public function __construct(
        string $index,
        ObjectIdentifier $table,
        ?AlterTableAlgorithm $algorithm = null,
        ?AlterTableLock $lock = null
    ) {
        $this->index = $index;
        $this->table = $table;
        $this->algorithm = $algorithm;
        $this->lock = $lock;
    }

    public function getIndex(): ObjectIdentifier
    {
        $schema = $this->table instanceof QualifiedName ? $this->table->schema : null;

        return $schema !== null ? new QualifiedName($this->index, $schema) : new SimpleName($this->index);
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP INDEX ' . $formatter->formatName($this->index) . ' ON ' . $this->table->serialize($formatter);
        if ($this->algorithm !== null) {
            $result .= ' ALGORITHM ' . $this->algorithm->serialize($formatter);
        }
        if ($this->lock !== null) {
            $result .= ' LOCK ' . $this->lock->serialize($formatter);
        }

        return $result;
    }

}
