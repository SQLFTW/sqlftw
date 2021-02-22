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
use SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition;

class ForeignKeyReflection
{
    use StrictBehaviorMixin;

    /** @var TableReflection */
    private $table;

    /** @var ForeignKeyDefinition */
    private $foreignKeyDefinition;

    /** @var ColumnReflection|null */
    private $originColumn;

    public function __construct(
        TableReflection $table,
        ForeignKeyDefinition $foreignKeyDefinition,
        ?ColumnReflection $originColumn = null
    )
    {
        $this->table = $table;
        $this->foreignKeyDefinition = $foreignKeyDefinition;
        $this->originColumn = $originColumn;
    }

    public function getTable(): TableReflection
    {
        return $this->table;
    }

    public function getForeignKeyDefinition(): ForeignKeyDefinition
    {
        return $this->foreignKeyDefinition;
    }

    public function getOriginColumn(): ?ColumnReflection
    {
        return $this->originColumn;
    }

}
