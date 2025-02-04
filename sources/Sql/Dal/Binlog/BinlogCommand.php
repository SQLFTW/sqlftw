<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Binlog;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dal\DalCommand;

class BinlogCommand extends Command implements DalCommand
{

    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function serialize(Formatter $formatter): string
    {
        return "BINLOG " . $formatter->formatString($this->value);
    }

}
