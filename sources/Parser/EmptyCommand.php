<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Statement;

class EmptyCommand extends Statement implements Command
{
    use StrictBehaviorMixin;

    /** @var TokenList */
    private $tokenList;

    public function __construct(TokenList $tokenList, array $commentsBefore)
    {
        $this->tokenList = $tokenList;
        $this->commentsBefore = $commentsBefore;
    }

    public function getTokenList(): TokenList
    {
        return $this->tokenList;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->tokenList->serialize();
    }

}
