<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Set;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dal\DalCommand;
use SqlFtw\Sql\Scope;

class SetCommand implements DalCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dal\Set\SetAssignment[] */
    private $assignments = [];

    /**
     * @param \SqlFtw\Sql\Dal\Set\SetAssignment[] $assignments
     */
    public function __construct(array $assignments)
    {
        Check::array($assignments, 1);
        Check::itemsOfType($assignments, SetAssignment::class);

        $this->assignments = $assignments;
    }

    /**
     * @return \SqlFtw\Sql\Dal\Set\SetAssignment[]
     */
    public function getAssignments(): array
    {
        return $this->assignments;
    }

    /**
     * @param \SqlFtw\Sql\Scope $scope
     * @return \SqlFtw\Sql\Dal\Set\SetAssignment[]
     */
    public function getAssignmentsByScope(?Scope $scope = null): array
    {
        $assignments = [];
        foreach ($this->assignments as $assignment) {
            if ($assignment->getScope() === $scope) {
                $assignments[] = $assignment;
            }
        }
        return $assignments;
    }

    /**
     * @param string $variable
     * @param \SqlFtw\Sql\Scope|null $scope
     * @return \SqlFtw\Sql\Dal\Set\SetAssignment|null
     */
    public function getAssignment(string $variable, ?Scope $scope = null): ?SetAssignment
    {
        foreach ($this->assignments as $assignment) {
            if ($assignment->getVariable() !== $variable) {
                continue;
            }
            if ($scope !== null && $assignment->getScope() !== $scope) {
                continue;
            }
            return $assignment;
        }
        return null;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SET ' . $formatter->formatSerializablesList($this->assignments);
    }

}
