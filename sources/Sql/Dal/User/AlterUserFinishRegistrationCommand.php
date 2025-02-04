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
use SqlFtw\Sql\Node;
use SqlFtw\Sql\UserName;

class AlterUserFinishRegistrationCommand extends AlterUserRegistrationCommand
{

    /** @var UserName|FunctionCall */
    public Node $user;

    public int $factor;

    public string $authString;

    public bool $ifExists;

    /**
     * @param UserName|FunctionCall $user
     */
    public function __construct(Node $user, int $factor, string $authString, bool $ifExists = false)
    {
        $this->user = $user;
        $this->factor = $factor;
        $this->authString = $authString;
        $this->ifExists = $ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ALTER USER ' . ($this->ifExists ? 'IF EXISTS ' : '') . $this->user->serialize($formatter)
            . ' ' . $this->factor . ' FINISH REGISTRATION SET CHALLENGE_RESPONSE AS ' . $formatter->formatString($this->authString);
    }

}
