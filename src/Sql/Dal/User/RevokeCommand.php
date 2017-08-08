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

class RevokeCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dal\User\UserPrivilege[] */
    private $privileges;

    /** @var \SqlFtw\Sql\Dal\User\UserPrivilegeResource */
    private $resource;

    /** @var \SqlFtw\Sql\UserName[] */
    private $users;

    /**
     * @param \SqlFtw\Sql\Dal\User\UserPrivilege[] $privileges
     * @param \SqlFtw\Sql\Dal\User\UserPrivilegeResource $resource
     * @param \SqlFtw\Sql\UserName[] $users
     */
    public function __construct(array $privileges, UserPrivilegeResource $resource, array $users)
    {
        $this->privileges = $privileges;
        $this->resource = $resource;
        $this->users = $users;
    }

    /**
     * @return \SqlFtw\Sql\Dal\User\UserPrivilege[]
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
     * @return \SqlFtw\Sql\UserName[]
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
