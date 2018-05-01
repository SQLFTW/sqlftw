<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Index;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableAlgorithm;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableLock;
use SqlFtw\Sql\Ddl\Table\TableStructureCommand;
use SqlFtw\Sql\QualifiedName;

class DropIndexCommand implements IndexCommand, TableStructureCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var \SqlFtw\Sql\QualifiedName */
    private $table;

    /** @var \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAlgorithm|null */
    private $algorithm;

    /** @var \SqlFtw\Sql\Ddl\Table\Alter\AlterTableLock|null */
    private $lock;

    public function __construct(
        string $name,
        QualifiedName $table,
        ?AlterTableAlgorithm $algorithm = null,
        ?AlterTableLock $lock = null
    ) {
        $this->name = $name;
        $this->table = $table;
        $this->algorithm = $algorithm;
        $this->lock = $lock;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    public function getAlgorithm(): ?AlterTableAlgorithm
    {
        return $this->algorithm;
    }

    public function getLock(): ?AlterTableLock
    {
        return $this->lock;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP INDEX ' . $formatter->formatName($this->name) . ' ON ' . $this->table->serialize($formatter);
        if ($this->algorithm !== null) {
            $result .= ' ALGORITHM ' . $this->algorithm->serialize($formatter);
        }
        if ($this->lock !== null) {
            $result .= ' LOCK ' . $this->lock->serialize($formatter);
        }

        return $result;
    }

}
