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
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\UserName;

class SetRoleCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dal\User\RolesSpecification */
    private $roles;

    /** @var \SqlFtw\Sql\UserName[]|null */
    private $rolesList;

    /**
     * @param \SqlFtw\Sql\Dal\User\RolesSpecification $roles
     * @param \SqlFtw\Sql\UserName[]|null $rolesList
     */
    public function __construct(?RolesSpecification $roles, ?array $rolesList = null)
    {
        if ($rolesList !== null) {
            Check::array($rolesList, 1);
            Check::itemsOfType($rolesList, UserName::class);
        }

        $this->roles = $roles;
        $this->rolesList = $rolesList;
    }

    public function getRoles(): ?RolesSpecification
    {
        return $this->roles;
    }

    /**
     * @return \SqlFtw\Sql\UserName[]
     */
    public function getRolesList(): array
    {
        return $this->rolesList;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SET ROLE ';
        if ($this->roles !== null) {
            $result .= $this->roles->serialize($formatter);
        }
        if ($this->rolesList !== null) {
            $result .= $formatter->formatSerializablesList($this->rolesList);
        }

        return $result;
    }

}
