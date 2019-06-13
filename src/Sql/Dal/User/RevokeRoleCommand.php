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

class RevokeRoleCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var string[] */
    private $roles;

    /** @var \SqlFtw\Sql\UserName[] */
    private $users;

    /**
     * @param string[] $roles
     * @param \SqlFtw\Sql\UserName[] $users
     */
    public function __construct(array $roles, array $users)
    {
        Check::itemsOfType($roles, Type::STRING);
        Check::itemsOfType($users, UserName::class);

        $this->roles = $roles;
        $this->users = $users;
    }

    /**
     * @return string[]
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

    public function serialize(Formatter $formatter): string
    {
        return 'REVOKE ' . $formatter->formatNamesList($this->roles)
            . ' FROM ' . $formatter->formatSerializablesList($this->users);
    }

}
