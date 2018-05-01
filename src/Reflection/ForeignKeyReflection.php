<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition;
use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintType;
use SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition;

class ForeignKeyReflection
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Reflection\TableReflection */
    private $table;

    /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition */
    private $constraintDefinition;

    public function __construct(TableReflection $table, ConstraintDefinition $constraintDefinition)
    {
        $this->table = $table;
        $this->constraintDefinition = $constraintDefinition;
    }

    public static function fromForeignKey(TableReflection $table, ForeignKeyDefinition $foreignKeyDefinition): self
    {
        $constraintDefinition = new ConstraintDefinition(
            ConstraintType::get(ConstraintType::FOREIGN_KEY),
            null,
            $foreignKeyDefinition
        );

        return new self($table, $constraintDefinition);
    }

    public function getTable(): TableReflection
    {
        return $this->table;
    }

    public function getConstraintDefinition(): ConstraintDefinition
    {
        return $this->constraintDefinition;
    }

}
