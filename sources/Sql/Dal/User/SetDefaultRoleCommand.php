<?php declare(strict_types = 1);
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
use SqlFtw\Sql\Statement;
use SqlFtw\Sql\UserName;

class SetDefaultRoleCommand extends Statement implements UserCommand
{

    /** @var non-empty-array<UserName|FunctionCall> */
    private $users;

    /** @var UserDefaultRolesSpecification|null */
    private $roles;

    /** @var non-empty-array<UserName>|null */
    private $rolesList;

    /**
     * @param non-empty-array<UserName|FunctionCall> $users
     * @param non-empty-array<UserName>|null $rolesList
     */
    public function __construct(array $users, ?UserDefaultRolesSpecification $roles, ?array $rolesList = null)
    {
        $this->users = $users;
        $this->roles = $roles;
        $this->rolesList = $rolesList;
    }

    /**
     * @return non-empty-array<UserName|FunctionCall>
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function getRoles(): ?UserDefaultRolesSpecification
    {
        return $this->roles;
    }

    /**
     * @return non-empty-array<UserName>|null
     */
    public function getRolesList(): ?array
    {
        return $this->rolesList;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SET DEFAULT ROLE ';
        if ($this->roles !== null) {
            $result .= $this->roles->serialize($formatter);
        }
        if ($this->rolesList !== null) {
            $result .= $formatter->formatSerializablesList($this->rolesList);
        }
        $result .= ' TO ' . $formatter->formatSerializablesList($this->users);

        return $result;
    }

}
