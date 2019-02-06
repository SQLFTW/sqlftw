<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\Arr;
use Dogma\ExceptionValueFormatter;
use function array_map;
use function array_slice;
use function implode;
use function is_array;
use function sprintf;

class UnexpectedTokenException extends ParserException
{

    /**
     * @param int[] $expectedTokens
     * @param mixed $expectedValue
     * @param \SqlFtw\Parser\Token|null $token
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @param \Throwable|null $previous
     */
    public function __construct(array $expectedTokens, $expectedValue, ?Token $token, TokenList $tokenList, ?\Throwable $previous = null)
    {
        $expectedToken = implode(', ', Arr::map($expectedTokens, function (int $type) {
            return implode('|', TokenType::get($type)->getConstantNames());
        }));
        if ($expectedValue !== null) {
            if (is_array($expectedValue)) {
                $expectedValue = implode(' or ', array_map(function ($value): string {
                    return ExceptionValueFormatter::format($value);
                }, $expectedValue));
                $expectedValue = sprintf(' with value %s', $expectedValue);
            } else {
                $expectedValue = sprintf(' with value %s', ExceptionValueFormatter::format($expectedValue));
            }
        }

        $context = $this->formatContext($tokenList);

        if ($token === null) {
            parent::__construct(sprintf(
                "Expected token %s%s, but end of query found instead at position %d in:\n%s",
                $expectedToken,
                $expectedValue,
                $tokenList->getPosition(),
                $context
            ), $previous);
            return;
        }

        $actualToken = implode('|', TokenType::get($token->type)->getConstantNames());
        $actualValue = ExceptionValueFormatter::format($token->value);

        parent::__construct(sprintf(
            "Expected token %s%s, but token %s with value %s found instead at position %d in:\n%s",
            $expectedToken,
            $expectedValue,
            $actualToken,
            $actualValue,
            $tokenList->getPosition(),
            $context
        ), $previous);
    }

    private function formatContext(TokenList $tokenList): string
    {
        $tokens = $tokenList->getTokens($tokenList->getPosition() - 10, 21);
        $context = '"…' . implode('', array_map(function (Token $token) {
            return $token->original ?? $token->value;
        }, array_slice($tokens, 0, 10)));

        if (isset($tokens[10])) {
            $context .= '»' . ($tokens[10]->original ?? $tokens[10]->value) . '«';
            $context .= implode('', array_map(function (Token $token) {
                return $token->original ?? $token->value;
            }, array_slice($tokens, 11, 10))) . '…"';
        } else {
            $context .= '»«"';
        }

        return $context;
    }

}
