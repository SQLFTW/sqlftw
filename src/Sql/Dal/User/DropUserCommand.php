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
use SqlFtw\Sql\Names\UserName;
use SqlFtw\SqlFormatter\SqlFormatter;

class DropUserCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\UserName[] */
    private $users;

    /** @var bool */
    private $ifExists;

    /**
     * @param \SqlFtw\Sql\Names\UserName[] $users
     * @param bool $ifExists
     */
    public function __construct(array $users, bool $ifExists = false)
    {
        Check::array($users, 1);
        Check::itemsOfType($users, UserName::class);

        $this->users = $users;
        $this->ifExists = $ifExists;
    }

    /**
     * @return \SqlFtw\Sql\Names\UserName[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'DROP USER ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $formatter->formatSerializablesList($this->users);

        return $result;
    }

}
