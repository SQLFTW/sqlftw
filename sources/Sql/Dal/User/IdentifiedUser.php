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

class IdentifiedUser extends Node
{

    /** @var UserName|FunctionCall */
    public Node $user;

    public ?AuthOption $option1;

    public ?AuthOption $option2;

    public ?AuthOption $option3;

    /**
     * @param UserName|FunctionCall $user
     */
    public function __construct(
        Node $user,
        ?AuthOption $option1 = null,
        ?AuthOption $option2 = null,
        ?AuthOption $option3 = null
    ) {
        $this->user = $user;
        $this->option1 = $option1;
        $this->option2 = $option2;
        $this->option3 = $option3;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->user->serialize($formatter);

        if ($this->option1 !== null) {
            $result .= ' ' . $this->option1->serialize($formatter);
            if ($this->option2 !== null) {
                $result .= ' AND ' . $this->option2->serialize($formatter);
                if ($this->option3 !== null) {
                    $result .= ' AND ' . $this->option3->serialize($formatter);
                }
            }
        }

        return $result;
    }

}
