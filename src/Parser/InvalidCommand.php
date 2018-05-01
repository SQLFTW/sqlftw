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

class InvalidCommand implements Command
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\TokenList */
    private $tokenList;

    /** @var \Throwable|null */
    private $exception;

    public function __construct(TokenList $tokenList, ?\Throwable $exception = null)
    {
        $this->tokenList = $tokenList;
        $this->exception = $exception;
    }

    public function getTokenList(): TokenList
    {
        return $this->tokenList;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->tokenList->serialize();
    }

}
