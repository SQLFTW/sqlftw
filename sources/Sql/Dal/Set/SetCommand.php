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
use SqlFtw\Sql\Dal\DalCommand;

interface SetCommand extends DalCommand
{

    /**
     * @return list<Assignment>
     */
    public function getAssignments(): array;

}
