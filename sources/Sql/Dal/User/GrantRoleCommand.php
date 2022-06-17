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
use SqlFtw\Sql\Statement;
use SqlFtw\Sql\UserName;

class GrantRoleCommand extends Statement implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<UserName> */
    private $roles;

    /** @var non-empty-array<UserName|FunctionCall> */
    private $users;

    /** @var bool */
    private $withAdminOption;

    /**
     * @param non-empty-array<UserName> $roles
     * @param non-empty-array<UserName|FunctionCall> $users
     */
    public function __construct(array $roles, array $users, bool $withAdminOption = false)
    {
        $this->roles = $roles;
        $this->users = $users;
        $this->withAdminOption = $withAdminOption;
    }

    /**
     * @return non-empty-array<UserName>
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

    public function withAdminOption(): bool
    {
        return $this->withAdminOption;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'GRANT ' . $formatter->formatSerializablesList($this->roles)
            . ' TO ' . $formatter->formatSerializablesList($this->users);

        if ($this->withAdminOption) {
            $result .= ' WITH ADMIN OPTION';
        }

        return $result;
    }

}
