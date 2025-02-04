<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Set;

use SqlFtw\Sql\Assignment;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dal\DalCommand;

abstract class SetCommand extends Command implements DalCommand
{

    /** @var list<Assignment> */
    public array $assignments = [];

}
