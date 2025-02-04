<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Node;
use SqlFtw\Sql\UserName;

class UserExpression extends Node
{

    /** @var UserName|BuiltInFunction */
    public Node $user;

    /**
     * @param UserName|BuiltInFunction $user
     */
    public function __construct(Node $user)
    {
        if ($user instanceof BuiltInFunction && $user->name !== BuiltInFunction::CURRENT_USER) {
            throw new InvalidDefinitionException('Only CURRENT_USER function is accepted in place of user name.');
        }

        $this->user = $user;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->user->serialize($formatter);
    }

}
