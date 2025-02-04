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

class SetDefaultRoleCommand extends UserCommand
{

    /** @var non-empty-list<UserName|FunctionCall> */
    public array $users;

    public ?UserDefaultRolesSpecification $roles;

    /** @var non-empty-list<UserName>|null */
    public $rolesList;

    /**
     * @param non-empty-list<UserName|FunctionCall> $users
     * @param non-empty-list<UserName>|null $rolesList
     */
    public function __construct(array $users, ?UserDefaultRolesSpecification $roles, ?array $rolesList = null)
    {
        $this->users = $users;
        $this->roles = $roles;
        $this->rolesList = $rolesList;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SET DEFAULT ROLE ';
        if ($this->roles !== null) {
            $result .= $this->roles->serialize($formatter);
        }
        if ($this->rolesList !== null) {
            $result .= $formatter->formatNodesList($this->rolesList);
        }
        $result .= ' TO ' . $formatter->formatNodesList($this->users);

        return $result;
    }

}
