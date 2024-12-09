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
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Sql\StatementImpl;
use SqlFtw\Sql\UserName;

class AlterUserUnregisterCommand extends StatementImpl implements AlterUserRegistrationCommand
{

    /** @var UserName|FunctionCall */
    public SqlSerializable $user;

    public int $factor;

    public bool $ifExists;

    /**
     * @param UserName|FunctionCall $user
     */
    public function __construct(SqlSerializable $user, int $factor, bool $ifExists = false)
    {
        $this->user = $user;
        $this->factor = $factor;
        $this->ifExists = $ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ALTER USER ' . ($this->ifExists ? 'IF EXISTS ' : '') . $this->user->serialize($formatter)
            . ' ' . $this->factor . ' UNREGISTER';
    }

}
