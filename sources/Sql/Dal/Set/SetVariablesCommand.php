<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Set;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

class SetVariablesCommand implements SetCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<SetAssignment> */
    private $assignments;

    /**
     * @param non-empty-array<SetAssignment> $assignments
     */
    public function __construct(array $assignments)
    {
        $this->assignments = $assignments;
    }

    /**
     * @return non-empty-array<SetAssignment>
     */
    public function getAssignments(): array
    {
        return $this->assignments;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SET ' . $formatter->formatSerializablesList($this->assignments);
    }

}
