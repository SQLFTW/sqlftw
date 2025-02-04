<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Expression\ObjectIdentifier;

class ShowCreateProcedureCommand extends ShowCommand
{

    public ObjectIdentifier $procedure;

    public function __construct(ObjectIdentifier $procedure)
    {
        $this->procedure = $procedure;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SHOW CREATE PROCEDURE ' . $this->procedure->serialize($formatter);
    }

}
