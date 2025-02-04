<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Utility;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\DmlCommand;

class DelimiterCommand extends Command implements DmlCommand
{

    public string $newDelimiter;

    public function __construct(string $newDelimiter)
    {
        $this->newDelimiter = $newDelimiter;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DELIMITER ' . $this->newDelimiter;
    }

}
