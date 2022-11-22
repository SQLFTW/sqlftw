<?php declare(strict_types = 1);
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
use SqlFtw\Sql\Statement;
use SqlFtw\Sql\UserName;

class RevokeProxyCommand extends Statement implements UserCommand
{

    /** @var UserName|FunctionCall */
    private $proxy;

    /** @var non-empty-list<UserName|FunctionCall> */
    private $users;

    /**
     * @param UserName|FunctionCall $proxy
     * @param non-empty-list<UserName|FunctionCall> $users
     */
    public function __construct($proxy, array $users)
    {
        $this->proxy = $proxy;
        $this->users = $users;
    }

    /**
     * @return UserName|FunctionCall
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * @return non-empty-list<UserName|FunctionCall>
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'REVOKE PROXY ON ' . $this->proxy->serialize($formatter)
            . ' FROM ' . $formatter->formatSerializablesList($this->users);
    }

}
