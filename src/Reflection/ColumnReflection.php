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

class ColumnReflection
{
    use StrictBehaviorMixin;

    /** @var TableReflection */
    private $table;

    /** @var ColumnDefinition */
    private $columnDefinition;

    public function __construct(TableReflection $table, ColumnDefinition $columnDefinition)
    {
        $this->table = $table;
        $this->columnDefinition = $columnDefinition;
    }

    public function getTable(): TableReflection
    {
        return $this->table;
    }

    public function getColumnDefinition(): ColumnDefinition
    {
        return $this->columnDefinition;
    }

}
