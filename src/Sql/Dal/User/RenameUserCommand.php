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

class RenameUserCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\UserName[] */
    protected $users;

    /** @var \SqlFtw\Sql\Names\UserName[] */
    private $newUsers;

    /**
     * @param \SqlFtw\Sql\Names\UserName[] $users
     * @param \SqlFtw\Sql\Names\UserName[] $newUsers
     */
    public function __construct(array $users, array $newUsers)
    {
        Check::array($users, 1);
        Check::itemsOfType($users, UserName::class);
        Check::array($newUsers, 1);
        Check::itemsOfType($newUsers, UserName::class);
        if (count($users) !== count($newUsers)) {
            throw new \SqlFtw\Sql\InvalidDefinitionException('Count of old user names and new user names do not match.');
        }

        $this->users = array_values($users);
        $this->newUsers = array_values($newUsers);
    }

    /**
     * @return \SqlFtw\Sql\Names\UserName[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @return \SqlFtw\Sql\Names\UserName[]
     */
    public function getNewUsers(): array
    {
        return $this->newUsers;
    }

    public function getIterator(): \IteratorAggregate
    {
        /// zip iterator
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'RENAME USER';
        foreach ($this->users as $i => $user) {
            $result .= ' ' . $user->serialize($formatter) . ' TO ' . $this->newUsers[$i]->serialize($formatter) . ',';
        }

        return rtrim($result, ',');
    }

}
