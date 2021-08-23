<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\UserName;

class RevokeCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var UserPrivilege[] */
    private $privileges;

    /** @var UserPrivilegeResource */
    private $resource;

    /** @var UserName[] */
    private $users;

    /**
     * @param UserPrivilege[] $privileges
     * @param UserPrivilegeResource $resource
     * @param UserName[] $users
     */
    public function __construct(array $privileges, UserPrivilegeResource $resource, array $users)
    {
        $this->privileges = $privileges;
        $this->resource = $resource;
        $this->users = $users;
    }

    /**
     * @return UserPrivilege[]
     */
    public function getPrivileges(): array
    {
        return $this->privileges;
    }

    public function getResource(): UserPrivilegeResource
    {
        return $this->resource;
    }

    /**
     * @return UserName[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'REVOKE ' . $formatter->formatSerializablesList($this->privileges)
            . ' ON ' . $this->resource->serialize($formatter)
            . ' FROM ' . $formatter->formatSerializablesList($this->users);
    }

}
