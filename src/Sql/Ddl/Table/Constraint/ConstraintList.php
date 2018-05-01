<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Constraint;

use Dogma\StrictBehaviorMixin;

class ConstraintList
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition[] (string|int $name => $constraint) */
    private $constraints = [];

    /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition[] (string|int $name => $constraint) */
    private $droppedConstraints = [];

    /**
     * @param \SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition[] $constraints
     */
    public function __construct(array $constraints)
    {
        foreach ($constraints as $constraint) {
            $this->addConstraint($constraint);
        }
    }

    private function addConstraint(ConstraintDefinition $constraint): void
    {
        if ($constraint->getName() !== null) {
            $this->constraints[$constraint->getName()] = $constraint;
        } else {
            $this->constraints[] = $constraint;
        }
    }

    public function updateRenamedConstraint(ConstraintDefinition $renamedConstraint, ?string $newName = null): void
    {
        foreach ($this->constraints as $oldName => $constraint) {
            if ($constraint === $renamedConstraint) {
                unset($this->constraints[$oldName]);
                if ($newName !== null) {
                    $this->constraints[$newName] = $constraint;
                } else {
                    $this->constraints[] = $constraint;
                }
            }
        }
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition[]
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition[]
     */
    public function getDroppedConstraints(): array
    {
        return $this->droppedConstraints;
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition[]
     */
    public function getForeignKeys(): array
    {
        /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition[] $result */
        $result = array_filter($this->constraints, function (ConstraintDefinition $constraint) {
            return $constraint instanceof ForeignKeyDefinition;
        });
        return $result;
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition[]
     */
    public function getDroppedForeignKeys(): array
    {
        /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition[] $result */
        $result = array_filter($this->droppedConstraints, function (ConstraintDefinition $constraint) {
            return $constraint instanceof ForeignKeyDefinition;
        });
        return $result;
    }

}
