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
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\StatementImpl;
use SqlFtw\Sql\UserName;

class DropUserCommand extends StatementImpl implements UserCommand
{

    /** @var non-empty-list<UserName|FunctionCall> */
    public array $users;

    public bool $ifExists;

    /**
     * @param non-empty-list<UserName|FunctionCall> $users
     */
    public function __construct(array $users, bool $ifExists = false)
    {
        $this->users = $users;
        $this->ifExists = $ifExists;
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
