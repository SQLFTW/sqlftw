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
use SqlFtw\Sql\Statement;

class CloseCursorStatement extends Statement
{

    public string $cursor;

    public function __construct(string $cursor)
    {
        $this->cursor = $cursor;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'CLOSE ' . $formatter->formatName($this->cursor);
    }

}
