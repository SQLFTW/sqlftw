<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Set;

use Dogma\Check;
use SqlFtw\Sql\SetAssignment;
use SqlFtw\Sql\Scope;
use SqlFtw\SqlFormatter\SqlFormatter;

class SetCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\SetAssignment[] */
    private $assignments = [];

    public function __construct(array $assignments)
    {
        Check::array($assignments, 1);
        Check::itemsOfType($assignments, SetAssignment::class);

        $this->assignments = $assignments;
    }

    /**
     * @return \SqlFtw\Sql\SetAssignment[]
     */
    public function getAssignments(): array
    {
        return $this->assignments;
    }

    /**
     * @param \SqlFtw\Sql\Scope $scope
     * @return \SqlFtw\Sql\SetAssignment[]
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
     * @return \SqlFtw\Sql\SetAssignment|null
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

    public function serialize(SqlFormatter $formatter): string
    {
        return 'SET ' . $formatter->formatSerializablesList($this->assignments);
    }

}
