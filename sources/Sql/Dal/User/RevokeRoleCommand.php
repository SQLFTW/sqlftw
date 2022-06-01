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
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\UserName;

class RevokeRoleCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<string> */
    private $roles;

    /** @var non-empty-array<UserName|FunctionCall> */
    private $users;

    /**
     * @param non-empty-array<string> $roles
     * @param non-empty-array<UserName|FunctionCall> $users
     */
    public function __construct(array $roles, array $users)
    {
        $this->roles = $roles;
        $this->users = $users;
    }

    /**
     * @return non-empty-array<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return non-empty-array<UserName|FunctionCall>
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
