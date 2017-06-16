<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Sql\Names\UserName;
use SqlFtw\SqlFormatter\SqlFormatter;

class RevokeProxyCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\UserName */
    private $proxy;

    /** @var \SqlFtw\Sql\Names\UserName[] */
    private $users;

    /**
     * @param \SqlFtw\Sql\Names\UserName $proxy
     * @param \SqlFtw\Sql\Names\UserName[] $users
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
     * @return \SqlFtw\Sql\Names\UserName[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'REVOKE PROXY ON ' . $this->proxy->serialize($formatter)
            . ' FROM ' . $formatter->formatSerializablesList($this->users);
    }

}
