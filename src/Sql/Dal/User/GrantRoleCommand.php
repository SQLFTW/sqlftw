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

class GrantRoleCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\UserName[] */
    private $roles;

    /** @var \SqlFtw\Sql\UserName[] */
    private $users;

    /** @var bool */
    private $withAdminOption;

    /**
     * @param \SqlFtw\Sql\UserName[] $roles
     * @param \SqlFtw\Sql\UserName[] $users
     * @param bool $withAdminOption
     */
    public function __construct(array $roles, array $users, bool $withAdminOption = false)
    {
        Check::itemsOfType($roles, UserName::class);
        Check::itemsOfType($users, UserName::class);

        $this->roles = $roles;
        $this->users = $users;
        $this->withAdminOption = $withAdminOption;
    }

    /**
     * @return \SqlFtw\Sql\UserName[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return \SqlFtw\Sql\UserName[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function withAdminOption(): bool
    {
        return $this->withAdminOption;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'GRANT ' . $formatter->formatSerializablesList($this->roles)
            . ' ON ' . $formatter->formatSerializablesList($this->users);
    }

}
