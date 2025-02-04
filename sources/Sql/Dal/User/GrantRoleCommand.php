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
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\UserName;

class GrantRoleCommand extends UserCommand
{

    /** @var non-empty-list<UserName> */
    public array $roles;

    /** @var non-empty-list<UserName|FunctionCall> */
    public array $users;

    public bool $withAdminOption;

    /**
     * @param non-empty-list<UserName> $roles
     * @param non-empty-list<UserName|FunctionCall> $users
     */
    public function __construct(array $roles, array $users, bool $withAdminOption = false)
    {
        $this->roles = $roles;
        $this->users = $users;
        $this->withAdminOption = $withAdminOption;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'GRANT ' . $formatter->formatNodesList($this->roles)
            . ' TO ' . $formatter->formatNodesList($this->users);

        if ($this->withAdminOption) {
            $result .= ' WITH ADMIN OPTION';
        }

        return $result;
    }

}
