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

class AlteredUser extends Node
{

    /** @var UserName|FunctionCall */
    public Node $user;

    public ?AlterUserAction $action;

    /**
     * @param UserName|FunctionCall $user
     */
    public function __construct(Node $user, ?AlterUserAction $action = null)
    {
        $this->user = $user;
        $this->action = $action;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->user->serialize($formatter);

        if ($this->action !== null) {
            $result .= ' ' . $this->action->serialize($formatter);
        }

        return $result;
    }

}
