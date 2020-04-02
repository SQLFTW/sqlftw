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
use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition;
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;

class IndexReflection
{
    use StrictBehaviorMixin;

    /** @var TableReflection */
    private $table;

    /** @var IndexDefinition */
    private $indexDefinition;

    /** @var ColumnDefinition|null */
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

    public static function fromConstraint(TableReflection $table, ConstraintDefinition $constraintDefinition): self
    {
        return new self($table, $constraintDefinition->getIndexDefinition());
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
