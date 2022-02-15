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
use SqlFtw\Sql\QualifiedName;

class ColumnReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $tableName;

    /** @var ColumnDefinition */
    private $columnDefinition;

    public function __construct(QualifiedName $table, ColumnDefinition $columnDefinition)
    {
        $this->tableName = $table;
        $this->columnDefinition = $columnDefinition;
    }

    public function getTableName(): QualifiedName
    {
        return $this->tableName;
    }

    public function getName(): string
    {
        return $this->columnDefinition->getName();
    }

    public function getColumnDefinition(): ColumnDefinition
    {
        return $this->columnDefinition;
    }

}
