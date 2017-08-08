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

class DropRoleCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\UserName[] */
    private $roles;

    /** @var bool */
    private $ifExists;

    /**
     * @param \SqlFtw\Sql\UserName[] $roles
     * @param bool $ifExists
     */
    public function __construct(array $roles, bool $ifExists = false)
    {
        Check::array($roles, 1);
        Check::itemsOfType($roles, UserName::class);

        $this->roles = $roles;
        $this->ifExists = $ifExists;
    }

    /**
     * @return \SqlFtw\Sql\UserName[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP';
        $result .= ' USER ' . $formatter->formatSerializablesList($this->roles);

        return $result;
    }

}
