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
use SqlFtw\Sql\Ddl\Table\Constraint\CheckDefinition;

class CheckReflection
{
    use StrictBehaviorMixin;

    /** @var TableReflection */
    private $table;

    /** @var CheckDefinition */
    private $checkDefinition;

    /** @var ColumnReflection|null */
    private $originColumn;

    public function __construct(
        TableReflection $table,
        CheckDefinition $checkDefinition,
        ?ColumnReflection $originColumn = null
    )
    {
        $this->table = $table;
        $this->checkDefinition = $checkDefinition;
        $this->originColumn = $originColumn;
    }

    public function getTable(): TableReflection
    {
        return $this->table;
    }

    public function getCheckDefinition(): CheckDefinition
    {
        return $this->checkDefinition;
    }

    public function getOriginColumn(): ?ColumnReflection
    {
        return $this->originColumn;
    }

}
