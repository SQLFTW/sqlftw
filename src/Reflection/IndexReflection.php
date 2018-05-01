<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition;
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;

class IndexReflection
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Reflection\TableReflection */
    private $table;

    /** @var \SqlFtw\Sql\Ddl\Table\Index\IndexDefinition */
    private $indexDefinition;

    /** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition|null */
    private $columnDefinition;

    public function __construct(TableReflection $table, IndexDefinition $indexDefinition)
    {
        $this->table = $table;
        $this->indexDefinition = $indexDefinition;
    }

    public static function fromColumn(TableReflection $table, ColumnDefinition $columnDefinition): self
    {
        $indexDefinition = new IndexDefinition(
            null,
            $columnDefinition->getIndexType(),
            [$columnDefinition->getName()]
        );

        $self = new self($table, $indexDefinition);
        $self->columnDefinition = $columnDefinition;

        return $self;
    }

    public function getTable(): TableReflection
    {
        return $this->table;
    }

    public function getIndexDefinition(): IndexDefinition
    {
        return $this->indexDefinition;
    }

    public function getColumnDefinition(): ?ColumnDefinition
    {
        return $this->columnDefinition;
    }

}
