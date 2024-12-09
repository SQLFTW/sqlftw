<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\StatementImpl;

class FetchStatement extends StatementImpl
{

    public string $cursor;

    /** @var non-empty-list<string> */
    public array $variables;

    /**
     * @param non-empty-list<string> $variables
     */
    public function __construct(string $cursor, array $variables)
    {
        $this->cursor = $cursor;
        $this->variables = $variables;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'FETCH NEXT FROM ' . $formatter->formatName($this->cursor)
            . ' INTO ' . $formatter->formatNamesList($this->variables);
    }

}
