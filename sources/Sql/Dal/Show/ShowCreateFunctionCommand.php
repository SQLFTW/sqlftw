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

class ShowCreateFunctionCommand extends ShowCommand
{

    public ObjectIdentifier $function;

    public function __construct(ObjectIdentifier $function)
    {
        $this->function = $function;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SHOW CREATE FUNCTION ' . $this->function->serialize($formatter);
    }

}
