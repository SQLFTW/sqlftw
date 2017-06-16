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

class CreateRoleCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\UserName[] */
    private $roles;

    /** @var bool */
    private $ifNotExists;

    /**
     * @param \SqlFtw\Sql\Names\UserName[] $roles
     * @param bool $ifNotExists
     */
    public function __construct(array $roles, bool $ifNotExists = false)
    {
        Check::itemsOfType($roles, UserName::class);

        $this->roles = $roles;
        $this->ifNotExists = $ifNotExists;
    }

    /**
     * @return \SqlFtw\Sql\Names\UserName[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function ifNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'CREATE ROLE ' . ($this->ifNotExists ? 'IF NOT EXISTS' : '') . $formatter->formatSerializablesList($this->roles);
    }

}
