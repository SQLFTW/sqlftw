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

class HelpCommand extends Command implements DmlCommand
{

    public string $term;

    public function __construct(string $term)
    {
        $this->term = $term;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'HELP ' . $formatter->formatString($this->term);
    }

}
