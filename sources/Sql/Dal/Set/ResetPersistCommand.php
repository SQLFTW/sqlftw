<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Set;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dal\DalCommand;
use SqlFtw\Sql\MysqlVariable;
use SqlFtw\Sql\StatementImpl;

class ResetPersistCommand extends StatementImpl implements DalCommand
{

    public ?MysqlVariable $variable;

    public bool $ifExists;

    public function __construct(?MysqlVariable $variable, bool $ifExists = false)
    {
        $this->variable = $variable;
        $this->ifExists = $ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'RESET PERSIST';
        if ($this->ifExists) {
            $result .= ' IF EXISTS';
        }
        if ($this->variable !== null) {
            $result .= ' ' . $this->variable->serialize($formatter);
        }

        return $result;
    }

}
