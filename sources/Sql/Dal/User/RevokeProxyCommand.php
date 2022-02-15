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

class RevokeProxyCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var UserName */
    private $proxy;

    /** @var UserName[] */
    private $users;

    /**
     * @param UserName[] $users
     */
    public function __construct(UserName $proxy, array $users)
    {
        $this->proxy = $proxy;
        $this->users = $users;
    }

    public function getProxy(): UserName
    {
        return $this->proxy;
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
        return 'REVOKE PROXY ON ' . $this->proxy->serialize($formatter)
            . ' FROM ' . $formatter->formatSerializablesList($this->users);
    }

}
