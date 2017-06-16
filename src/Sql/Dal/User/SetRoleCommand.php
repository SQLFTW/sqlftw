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

class SetRoleCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dal\User\RolesSpecification */
    private $roles;

    /** @var \SqlFtw\Sql\Names\UserName[]|null */
    private $rolesList;

    /**
     * @param \SqlFtw\Sql\Dal\User\RolesSpecification $roles
     * @param \SqlFtw\Sql\Names\UserName[]|null $rolesList
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
     * @return \SqlFtw\Sql\Names\UserName[]
     */
    public function getRolesList(): array
    {
        return $this->rolesList;
    }

    public function serialize(SqlFormatter $formatter): string
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
