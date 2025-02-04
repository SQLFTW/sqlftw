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
use SqlFtw\Sql\Node;
use SqlFtw\Sql\UserName;

class RevokeProxyCommand extends UserCommand
{

    /** @var UserName|FunctionCall */
    public Node $proxy;

    /** @var non-empty-list<UserName|FunctionCall> */
    public array $users;

    public bool $ifExists;

    public bool $ignoreUnknownUser;

    /**
     * @param UserName|FunctionCall $proxy
     * @param non-empty-list<UserName|FunctionCall> $users
     */
    public function __construct(
        Node $proxy,
        array $users,
        bool $ifExists = false,
        bool $ignoreUnknownUser = false
    ) {
        $this->proxy = $proxy;
        $this->users = $users;
        $this->ifExists = $ifExists;
        $this->ignoreUnknownUser = $ignoreUnknownUser;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'REVOKE ' . ($this->ifExists ? 'IF EXISTS' : '')
            . ' PROXY ON ' . $this->proxy->serialize($formatter)
            . ' FROM ' . $formatter->formatNodesList($this->users)
            . ($this->ignoreUnknownUser ? ' IGNORE UNKNOWN USER' : '');
    }

}
