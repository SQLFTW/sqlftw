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
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\StatementImpl;

class DropProcedureCommand extends StatementImpl implements StoredProcedureCommand, DropRoutineCommand
{

    public ObjectIdentifier $procedure;

    public bool $ifExists;

    public function __construct(ObjectIdentifier $procedure, bool $ifExists = false)
    {
        $this->procedure = $procedure;
        $this->ifExists = $ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP PROCEDURE ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $this->procedure->serialize($formatter);

        return $result;
    }

}
