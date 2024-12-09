<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\StatementImpl;

class SetRoleCommand extends StatementImpl implements UserCommand
{

    public RolesSpecification $role;

    public function __construct(RolesSpecification $role)
    {
        $this->role = $role;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SET ROLE ' . $this->role->serialize($formatter);
    }

}
