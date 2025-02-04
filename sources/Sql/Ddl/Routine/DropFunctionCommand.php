<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Expression\ObjectIdentifier;

class DropFunctionCommand extends Command implements StoredFunctionCommand, DropRoutineCommand
{

    public ObjectIdentifier $function;

    public bool $ifExists;

    public function __construct(ObjectIdentifier $function, bool $ifExists = false)
    {
        $this->function = $function;
        $this->ifExists = $ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP FUNCTION ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $this->function->serialize($formatter);

        return $result;
    }

}
