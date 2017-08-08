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
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\UserName;

class GrantProxyCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\UserName */
    private $proxy;

    /** @var \SqlFtw\Sql\UserName[] */
    private $users;

    /** @var bool */
    private $withGrantOption;

    public function __construct(UserName $proxy, array $users, bool $withGrantOption = false)
    {
        Check::itemsOfType($users, UserName::class);

        $this->proxy = $proxy;
        $this->users = $users;
        $this->withGrantOption = $withGrantOption;
    }

    public function getProxy(): UserName
    {
        return $this->proxy;
    }

    /**
     * @return \SqlFtw\Sql\UserName[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function withGrantOption(): bool
    {
        return $this->withGrantOption;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'GRANT PROXY ON ' . $this->proxy->serialize($formatter)
            . ' TO ' . $formatter->formatSerializablesList($this->users)
            . ($this->withGrantOption ? ' WITH GRANT OPTION' : '');
    }

}
