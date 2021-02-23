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
use SqlFtw\Sql\Ddl\Table\DdlTableCommand;
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;
use SqlFtw\Sql\QualifiedName;

class CreateIndexCommand implements IndexCommand, DdlTableCommand
{
    use StrictBehaviorMixin;

    /** @var IndexDefinition */
    private $index;

    /** @var AlterTableAlgorithm|null */
    private $algorithm;

    /** @var AlterTableLock|null */
    private $lock;

    public function __construct(
        IndexDefinition $index,
        ?AlterTableAlgorithm $algorithm = null,
        ?AlterTableLock $lock = null
    ) {
        $this->index = $index;
        $this->algorithm = $algorithm;
        $this->lock = $lock;
    }

    public function getName(): QualifiedName
    {
        return new QualifiedName($this->getTable()->getSchema(), $this->index->getName());
    }

    public function getTable(): QualifiedName
    {
        return $this->index->getTable();
    }

    public function getIndex(): IndexDefinition
    {
        return $this->index;
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
        $result = 'CREATE ';
        $result .= $this->index->serializeHead($formatter);
        $result .= ' ON ' . $this->index->getTable()->serialize($formatter);
        $result .= $this->index->serializeTail($formatter);

        if ($this->algorithm !== null) {
            $result .= ' ALGORITHM ' . $this->algorithm->serialize($formatter);
        }
        if ($this->lock !== null) {
            $result .= ' LOCK ' . $this->lock->serialize($formatter);
        }

        return $result;
    }

}
