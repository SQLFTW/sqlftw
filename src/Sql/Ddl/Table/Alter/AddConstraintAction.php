<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition;
use SqlFtw\SqlFormatter\SqlFormatter;

class AddConstraintAction implements \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition */
    private $constraint;

    public function __construct(ConstraintDefinition $constraint)
    {
        $this->constraint = $constraint;
    }

    public function getType(): AlterTableActionType
    {
        return AlterTableActionType::get(AlterTableActionType::ADD_CONSTRAINT);
    }

    public function getConstraint(): ConstraintDefinition
    {
        return $this->constraint;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'ADD ' . $this->constraint->serialize($formatter);
    }

}
