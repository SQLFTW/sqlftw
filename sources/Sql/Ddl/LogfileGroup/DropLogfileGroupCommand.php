<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\LogfileGroup;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Table\Option\StorageEngine;

class DropLogfileGroupCommand extends Command implements LogfileGroupCommand
{

    public string $logFileGroup;

    public ?StorageEngine $engine;

    public function __construct(string $logFileGroup, ?StorageEngine $engine)
    {
        $this->logFileGroup = $logFileGroup;
        $this->engine = $engine;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP LOGFILE GROUP ' . $formatter->formatName($this->logFileGroup);
        if ($this->engine !== null) {
            $result .= ' ENGINE ' . $this->engine->serialize($formatter);
        }

        return $result;
    }

}
