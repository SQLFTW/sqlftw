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
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\StatementImpl;

class SetCharacterSetCommand extends StatementImpl implements SetCommand
{

    public ?Charset $charset;

    /** @var list<Assignment> */
    public array $assignments;

    /**
     * @param list<Assignment> $assignments
     */
    public function __construct(?Charset $charset, array $assignments = [])
    {
        $this->charset = $charset;
        $this->assignments = $assignments;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SET CHARACTER SET ' . ($this->charset !== null ? $this->charset->serialize($formatter) : 'DEFAULT');

        if ($this->assignments !== []) {
            $result .= ', ' . $formatter->formatSerializablesList($this->assignments);
        }

        return $result;
    }

}
