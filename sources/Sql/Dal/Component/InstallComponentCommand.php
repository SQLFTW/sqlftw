<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Component;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Assignment;
use SqlFtw\Sql\Command;

class InstallComponentCommand extends Command implements ComponentCommand
{

    /** @var non-empty-list<string> */
    public array $components;

    /** @var non-empty-list<Assignment> */
    public array $assignments;

    /**
     * @param non-empty-list<string> $components
     * @param non-empty-list<Assignment> $assignments
     */
    public function __construct(array $components, array $assignments)
    {
        $this->components = $components;
        $this->assignments = $assignments;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'INSTALL COMPONENT ' . $formatter->formatNamesList($this->components);
        if ($this->assignments !== []) {
            $result .= ' SET ' . $formatter->formatNodesList($this->assignments);
        }

        return $result;
    }

}
