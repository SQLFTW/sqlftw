<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\UserName;

class SetDefaultRoleCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var UserName[] */
    private $users;

    /** @var UserDefaultRolesSpecification|null */
    private $roles;

    /** @var string[]|null */
    private $rolesList;

    /**
     * @param UserName[] $users
     * @param string[]|null $rolesList
     */
    public function __construct(array $users, ?UserDefaultRolesSpecification $roles, ?array $rolesList = null)
    {
        Check::array($users, 1);
        Check::itemsOfType($users, UserName::class);
        if ($rolesList !== null) {
            Check::array($rolesList, 1);
            Check::itemsOfType($rolesList, Type::STRING);
        }

        $this->users = $users;
        $this->roles = $roles;
        $this->rolesList = $rolesList;
    }

    /**
     * @return UserName[]
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
     * @return string[]|null
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
            $result .= $formatter->formatNamesList($this->rolesList);
        }
        $result .= ' TO ' . $formatter->formatSerializablesList($this->users);

        return $result;
    }

}
