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
use Dogma\CombineIterator;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\UserName;

class RenameUserCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\UserName[] */
    protected $users;

    /** @var \SqlFtw\Sql\UserName[] */
    private $newUsers;

    /**
     * @param \SqlFtw\Sql\UserName[] $users
     * @param \SqlFtw\Sql\UserName[] $newUsers
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
     * @return \SqlFtw\Sql\UserName[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @return \SqlFtw\Sql\UserName[]
     */
    public function getNewUsers(): array
    {
        return $this->newUsers;
    }

    public function getIterator(): CombineIterator
    {
        return new CombineIterator($this->users, $this->newUsers);
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'RENAME USER';
        foreach ($this->users as $i => $user) {
            $result .= ' ' . $user->serialize($formatter) . ' TO ' . $this->newUsers[$i]->serialize($formatter) . ',';
        }

        return rtrim($result, ',');
    }

}
