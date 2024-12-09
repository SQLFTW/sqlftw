<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use SqlFtw\Formatter\Formatter;

class DropCheckAction implements CheckAction
{

    public string $check;

    public function __construct(string $check)
    {
        $this->check = $check;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DROP CHECK ' . $formatter->formatName($this->check);
    }

}
