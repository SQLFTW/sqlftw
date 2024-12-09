<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\StatementImpl;
use SqlFtw\Sql\UserName;

class ShowGrantsCommand extends StatementImpl implements ShowCommand
{

    public ?UserExpression $user;

    /** @var non-empty-list<UserName>|null */
    public ?array $roles;

    /**
     * @param non-empty-list<UserName> $roles
     */
    public function __construct(?UserExpression $user = null, ?array $roles = null)
    {
        $this->user = $user;
        $this->roles = $roles;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SHOW GRANTS';
        if ($this->user !== null) {
            $result .= ' FOR ' . $this->user->serialize($formatter);
            if ($this->roles !== null) {
                $result .= ' USING ' . $formatter->formatSerializablesList($this->roles);
            }
        }

        return $result;
    }

}
