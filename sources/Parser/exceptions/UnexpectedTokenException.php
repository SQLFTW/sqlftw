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
use Dogma\Str;
use Throwable;
use function array_map;
use function array_slice;
use function implode;
use function is_array;
use function max;
use function min;
use function sprintf;

class UnexpectedTokenException extends ParserException
{

    /**
     * @param int[] $expectedTokens
     * @param mixed $expectedValue
     */
    public function __construct(array $expectedTokens, $expectedValue, ?Token $token, TokenList $tokenList, ?Throwable $previous = null)
    {
        $expectedToken = implode(', ', Arr::map($expectedTokens, static function (int $type) {
            return implode('|', TokenType::get($type)->getConstantNames());
        }));
        if ($expectedValue !== null) {
            if (is_array($expectedValue)) {
                $expectedValue = Str::join(array_map(static function ($value): string {
                    return '"' . $value . '"';
                }, $expectedValue), ', ', ' or ', 120, '...');
                $expectedValue = " with value " . $expectedValue;
            } else {
                $expectedValue = " with value " . ExceptionValueFormatter::format($expectedValue);
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

        $actualToken = implode('|', TokenType::getByValue($token->type)->getConstantNames());
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
        $start = max($tokenList->getPosition() - 11, 0);
        $prefix = 10 - min(max(10 - $tokenList->getPosition(), 0), 10);
        $tokens = array_slice($tokenList->getTokens(), $start, 21);
        $context = '"…' . implode('', array_map(static function (Token $token) {
            return $token->original ?? $token->value;
        }, array_slice($tokens, 0, $prefix)));

        if (isset($tokens[$prefix])) {
            $context .= '»' . ($tokens[$prefix]->original ?? $tokens[$prefix]->value) . '«';
            $context .= implode('', array_map(static function (Token $token) {
                return $token->original ?? $token->value;
            }, array_slice($tokens, $prefix + 1))) . '…"';
        } else {
            $context .= '»«"';
        }

        return $context;
    }

}
