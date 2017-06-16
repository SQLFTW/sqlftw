<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

class UnexpectedTokenValueException extends \SqlFtw\Parser\ParserException
{

    /**
     * @param int $token
     * @param mixed $expectedValue
     * @param mixed $value
     * @param \Throwable|null $previous
     */
    public function __construct(int $token, $expectedValue, $value, ?\Throwable $previous = null)
    {
        $token = TokenType::get($token)->getConstantName();

        parent::__construct(
            sprintf('Expected token of type %s with value "%s". Found value "%s" instead.', $token, $expectedValue, $value),
            $previous
        );
    }

}
