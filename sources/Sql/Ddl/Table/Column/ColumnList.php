<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Column;

class ColumnList
{

    /**
     * @var array<string, ColumnDefinition> ($name => $column)
     */
    public array $columns = [];

    /**
     * @param array<string, ColumnDefinition> $columns
     */
    public function __construct(array $columns)
    {
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
    }

    private function addColumn(ColumnDefinition $column): void
    {
        $this->columns[$column->name] = $column;
    }

    public function containsColumn(ColumnDefinition $searchedColumn): bool
    {
        return $this->columns[$searchedColumn->name] === $searchedColumn;
    }

}
