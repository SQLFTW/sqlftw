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

class DropUserCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<UserName|FunctionCall> */
    private $users;

    /** @var bool */
    private $ifExists;

    /**
     * @param non-empty-array<UserName|FunctionCall> $users
     */
    public function __construct(array $users, bool $ifExists = false)
    {
        $this->users = $users;
        $this->ifExists = $ifExists;
    }

    /**
     * @return non-empty-array<UserName|FunctionCall>
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP USER ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $formatter->formatSerializablesList($this->users);

        return $result;
    }

}
