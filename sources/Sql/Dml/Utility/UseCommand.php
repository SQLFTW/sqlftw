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

class UseCommand extends Command implements DmlCommand
{

    public string $schema;

    public function __construct(string $schema)
    {
        $this->schema = $schema;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'USE ' . $formatter->formatName($this->schema);
    }

}
