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

class GrantProxyCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var UserName */
    private $proxy;

    /** @var non-empty-array<UserName> */
    private $users;

    /** @var bool */
    private $withGrantOption;

    /**
     * @param non-empty-array<UserName> $users
     */
    public function __construct(UserName $proxy, array $users, bool $withGrantOption = false)
    {
        $this->proxy = $proxy;
        $this->users = $users;
        $this->withGrantOption = $withGrantOption;
    }

    public function getProxy(): UserName
    {
        return $this->proxy;
    }

    /**
     * @return non-empty-array<UserName>
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
