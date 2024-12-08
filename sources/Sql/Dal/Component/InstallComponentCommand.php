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
use SqlFtw\Sql\StatementImpl;

class InstallComponentCommand extends StatementImpl implements ComponentCommand
{

    /** @var non-empty-list<string> */
    private array $components;

    /** @var non-empty-list<Assignment> */
    private array $assignments;

    /**
     * @param non-empty-list<string> $components
     * @param non-empty-list<Assignment> $assignments
     */
    public function __construct(array $components, array $assignments)
    {
        $this->components = $components;
        $this->assignments = $assignments;
    }

    /**
     * @return non-empty-list<string>
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'INSTALL COMPONENT ' . $formatter->formatNamesList($this->components);
        if ($this->assignments !== []) {
            $result .= ' SET ' . $formatter->formatSerializablesList($this->assignments);
        }

        return $result;
    }

}
