<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\Arr;

class UnexpectedTokenException extends \SqlFtw\Parser\ParserException
{

    /**
     * @param int[] $expectedTokens
     * @param int $token
     * @param \Throwable|null $previous
     */
    public function __construct(array $expectedTokens, int $token, ?\Throwable $previous = null)
    {
        $expected = implode(', ', Arr::map($expectedTokens, function (int $type) {
            return TokenType::get($type)->getConstantName();
        }));
        $actual = TokenType::get($token)->getConstantName();

        parent::__construct(
            sprintf('Expected token of type %s. Found %s instead.', $expected, $actual),
            $previous
        );
    }

}
