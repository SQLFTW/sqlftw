<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

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

class ParserException extends ParsingException
{

    /** @var TokenList */
    private $tokenList;

    public function __construct(string $message, TokenList $tokenList, ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);

        $this->tokenList = $tokenList;
    }

    public function getTokenList(): TokenList
    {
        return $this->tokenList;
    }

    /**
     * @param mixed $expectedValue
     */
    public static function tokens(int $expectedToken, int $tokenMask, $expectedValue, ?Token $token, TokenList $tokenList, ?Throwable $previous = null): self
    {
        $expectedToken = implode('|', TokenType::getByValue($expectedToken)->getConstantNames());
        if ($tokenMask !== 0) {
            $expectedToken .= ' ~' . implode('|', TokenType::getByValue($tokenMask)->getConstantNames());
        }

        if ($expectedValue !== null) {
            if (is_array($expectedValue)) {
                $expectedValue = Str::join($expectedValue, ', ', ' or ', 120, '...');
                $expectedValue = " with value " . $expectedValue;
            } else {
                $expectedValue = " with value " . ExceptionValueFormatter::format($expectedValue);
            }
        }

        $context = self::formatContext($tokenList);

        if ($token === null) {
            return new self(sprintf(
                "Expected token %s%s, but end of query found instead at position %d in:\n%s",
                $expectedToken,
                $expectedValue,
                $tokenList->getPosition(),
                $context
            ), $tokenList, $previous);
        }

        $actualToken = implode('|', TokenType::getByValue($token->type)->getConstantNames());
        $actualValue = ExceptionValueFormatter::format($token->value);

        return new self(sprintf(
            "Expected token %s%s, but token %s with value %s found instead at position %d in:\n%s",
            $expectedToken,
            $expectedValue,
            $actualToken,
            $actualValue,
            $tokenList->getPosition(),
            $context
        ), $tokenList, $previous);
    }

    protected static function formatContext(TokenList $tokenList): string
    {
        $before = 30;
        $after = 10;
        $start = max($tokenList->getPosition() - $before, 0);
        $prefix = $before - min(max($before - $tokenList->getPosition(), 0), $before);
        $tokens = array_slice($tokenList->getTokens(), $start, $before + $after + 1);
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
