<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\UserName;

class DropRoleCommand extends UserCommand
{

    /** @var non-empty-list<UserName> */
    public array $roles;

    public bool $ifExists;

    /**
     * @param non-empty-list<UserName> $roles
     */
    public function __construct(array $roles, bool $ifExists = false)
    {
        $this->roles = $roles;
        $this->ifExists = $ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP ROLE ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }

        return $result . $formatter->formatNodesList($this->roles);
    }

}
