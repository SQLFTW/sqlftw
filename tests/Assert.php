<?php declare(strict_types = 1);

namespace SqlFtw\Tests;

use Dogma\Tester\Assert as DogmaAssert;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenType;
use function gettype;
use function implode;
use function preg_replace;
use function sprintf;

class Assert extends DogmaAssert
{

    /**
     * @param mixed|null $value
     */
    public static function token(Token $token, int $type, $value = null, ?int $position = null): void
    {
        if ($type !== $token->type) {
            parent::fail(sprintf(
                'Type of token "%s" is %s (%d) and should be %s (%d).',
                $token->value,
                implode('|', TokenType::get($token->type)->getConstantNames()),
                $token->type,
                implode('|', TokenType::get($type)->getConstantNames()),
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

    public static function parse(
        string $query,
        ?string $expected = null,
        ?Parser $parser = null,
        ?Formatter $formatter = null
    ): void {
        /** @var string $query */
        $query = preg_replace('/\\s+/', ' ', $query);
        $query = str_replace(['( ', ' )'], ['(', ')'], $query);

        if ($expected !== null) {
            /** @var string $expected */
            $expected = preg_replace('/\\s+/', ' ', $expected);
            $expected = str_replace(['( ', ' )'], ['(', ')'], $expected);
        } else {
            $expected = $query;
        }

        $parser = $parser ?? ParserHelper::getParserFactory()->getParser();
        $formatter = $formatter ?? new Formatter($parser->getSettings());

        $actual = $parser->parseCommand($query)->serialize($formatter);
        /** @var string $actual */
        $actual = preg_replace('/\\s+/', ' ', $actual);
        $actual = str_replace(['( ', ' )'], ['(', ')'], $actual);

        self::same($expected, $actual);
    }

}
