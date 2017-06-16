<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use Dogma\Check;
use SqlFtw\Sql\Names\UserName;
use SqlFtw\SqlFormatter\SqlFormatter;

class AlterUserDefaultRoleCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    public const NO_ROLES = false;
    public const ALL_ROLES = true;
    public const LIST_ROLES = null;

    /** @var \SqlFtw\Sql\Names\UserName */
    private $user;

    /** @var \SqlFtw\Sql\Dal\User\RolesSpecification|null */
    private $roles;

    /** @var \SqlFtw\Sql\Names\UserName[]|null */
    private $rolesList;

    /** @var bool */
    private $ifExists;

    /**
     * @param \SqlFtw\Sql\Names\UserName $user
     * @param bool|null $roles
     * @param \SqlFtw\Sql\Names\UserName[]|null $rolesList
     * @param bool $ifExists
     */
    public function __construct(UserName $user, ?RolesSpecification $roles = null, ?array $rolesList = null, bool $ifExists = false)
    {
        if ($rolesList !== null) {
            Check::array($rolesList, 1);
            Check::itemsOfType($rolesList, UserName::class);
        }

        $this->user = $user;
        $this->roles = $roles;
        $this->rolesList = $rolesList;
        $this->ifExists = $ifExists;
    }

    public function getUser(): UserName
    {
        return $this->user;
    }

    public function getRoles(): ?RolesSpecification
    {
        return $this->roles;
    }

    /**
     * @return \SqlFtw\Sql\Names\UserName[]|null
     */
    public function getRolesList(): ?array
    {
        return $this->rolesList;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'ALTER USER ' . $this->user->serialize($formatter) . ' DEFAULT ROLE ';
        if ($this->roles !== null) {
            $result .= $this->roles->serialize($formatter);
        }
        if ($this->rolesList !== null) {
            $result .= $formatter->formatSerializablesList($this->rolesList);
        }
        if ($this->ifExists) {
            $result .= ' IF EXISTS';
        }

        return $result;
    }

}
