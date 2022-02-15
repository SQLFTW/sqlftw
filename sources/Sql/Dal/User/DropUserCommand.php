<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\UserName;

class DropUserCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var UserName[] */
    private $users;

    /** @var bool */
    private $ifExists;

    /**
     * @param UserName[] $users
     */
    public function __construct(array $users, bool $ifExists = false)
    {
        Check::array($users, 1);
        Check::itemsOfType($users, UserName::class);

        $this->users = $users;
        $this->ifExists = $ifExists;
    }

    /**
     * @return UserName[]
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
