<?php declare(strict_types = 1);

namespace SqlFtw\Tests;

use SqlFtw\Parser\Lexer\Lexer;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserFactory;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\Platform;
use SqlFtw\Platform\Settings;

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

    /*
    public static function parse(string $query, ?string $result, ?array $platforms): void
    {
        $result = $result ?? $query;
    }

    private function createPlatform(string $platform)
    {
        return Platform::get(...explode('-', $platform));
    }

    private function getParserFactory(?Platform $platform = null): ParserFactory
    {
        if ($platform === null) {
            $platform = Platform::get(Platform::MYSQL);
        }
        $settings = new Settings($platform);
        $settings->setQuoteAllNames(false);

        $lexer = new Lexer($settings, true, true);
        $parser = new Parser($lexer, $settings);

        return new ParserFactory($settings, $parser);
    }
    */

}
