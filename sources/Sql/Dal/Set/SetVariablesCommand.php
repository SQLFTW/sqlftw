<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Set;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Assignment;

class SetVariablesCommand extends SetCommand
{

    /** @var non-empty-list<Assignment> */
    public array $assignments; // @phpstan-ignore property.phpDocType (breaking LSP on writes by narrowing from list to non-empty-list)

    /**
     * @param non-empty-list<Assignment> $assignments
     */
    public function __construct(array $assignments)
    {
        $this->assignments = $assignments;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SET ' . $formatter->formatNodesList($this->assignments);
    }

}
