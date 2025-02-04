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

class GrantProxyCommand extends UserCommand
{

    /** @var UserName|FunctionCall */
    public Node $proxy;

    /** @var non-empty-list<IdentifiedUser|FunctionCall> */
    public array $users;

    public bool $withGrantOption;

    /**
     * @param UserName|FunctionCall $proxy
     * @param non-empty-list<IdentifiedUser|FunctionCall> $users
     */
    public function __construct(Node $proxy, array $users, bool $withGrantOption = false)
    {
        $this->proxy = $proxy;
        $this->users = $users;
        $this->withGrantOption = $withGrantOption;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'GRANT PROXY ON ' . $this->proxy->serialize($formatter)
            . ' TO ' . $formatter->formatNodesList($this->users)
            . ($this->withGrantOption ? ' WITH GRANT OPTION' : '');
    }

}
