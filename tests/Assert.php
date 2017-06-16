<?php declare(strict_types = 1);

namespace SqlFtw\Tests;

use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenType;

class Assert extends \Tester\Assert
{

    /**
     * @param \SqlFtw\Parser\Token $token
     * @param int $type
     * @param mixed|null $value
     * @param int|null $position
     */
    public static function token(Token $token, int $type, $value = null, ?int $position = null): void
    {
        if ($type !== $token->type) {
            parent::fail(sprintf(
                'Type of token "%s" is %s (%d) and should be %s (%d).',
                $token->value,
                implode('|', array_keys(iterator_to_array(TokenType::get($token->type)->getIterator()))),
                $token->type,
                implode('|', array_keys(iterator_to_array(TokenType::get($type)->getIterator()))),
                $type
            ));
        }
        if ($value !== $token->value) {
            parent::fail(sprintf(
                'Token value is "%s" (%s) and should be "%s" (%s).',
                $token->value,
                gettype($token->value),
                $value,
                gettype($value)
            ));
        }
        if ($position !== null && $position !== $token->position) {
            parent::fail(sprintf('Token starting position is %s and should be %s.', $token->position, $position));
        }
    }

}
