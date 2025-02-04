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

class RevokeCommand extends UserCommand
{

    /** @var non-empty-list<UserPrivilege> */
    public array $privileges;

    public UserPrivilegeResource $resource;

    /** @var non-empty-list<UserName|FunctionCall> */
    public array $users;

    public bool $ifExists;

    public bool $ignoreUnknownUser;

    /**
     * @param non-empty-list<UserPrivilege> $privileges
     * @param non-empty-list<UserName|FunctionCall> $users
     */
    public function __construct(
        array $privileges,
        UserPrivilegeResource $resource,
        array $users,
        bool $ifExists = false,
        bool $ignoreUnknownUser = false
    ) {
        $this->privileges = $privileges;
        $this->resource = $resource;
        $this->users = $users;
        $this->ifExists = $ifExists;
        $this->ignoreUnknownUser = $ignoreUnknownUser;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'REVOKE ' . ($this->ifExists ? 'IF EXISTS ' : '')
            . $formatter->formatNodesList($this->privileges)
            . ' ON ' . $this->resource->serialize($formatter)
            . ' FROM ' . $formatter->formatNodesList($this->users)
            . ($this->ignoreUnknownUser ? ' IGNORE UNKNOWN USER' : '');
    }

}
