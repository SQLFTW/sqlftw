<?php declare(strict_types = 1);

namespace Test;

use Dogma\Debug\Dumper;
use SqlFtw\Parser\ParserException;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\Platform;
use Tracy\Debugger;
use function array_slice;
use function class_exists;
use function ctype_space;
use function dirname;
use function get_class;
use function header;
use function implode;
use const PHP_SAPI;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/nette/tester/src/bootstrap.php';
require_once __DIR__ . '/ParserHelper.php';
require_once __DIR__ . '/Assert.php';

Debugger::$maxDepth = 9;
Debugger::$strictMode = true;

if (class_exists(Dumper::class)) {
    // TokenType value
    Dumper::$intFormatters = [
        '~tokenType~' => static function (int $int): string {
            $types = implode('|', TokenType::getByValue($int)->getConstantNames());

            return Dumper::int((string) $int) . ' ' . Dumper::info('// TokenType::' . $types);
        },
    ] + Dumper::$intFormatters;

    // Token
    Dumper::$objectFormatters[Token::class] = static function (Token $token, int $depth = 0): string {
        $type = implode('|', TokenType::getByValue($token->type)->getConstantNames());
        $info = Dumper::$showInfo;
        Dumper::$showInfo = false;

        $orig = $token->original !== null ? ' | ' . Dumper::value($token->original) : '';
        $res = Dumper::name(get_class($token)) . Dumper::bracket('(')
            . Dumper::dumpValue($token->value, $depth + 1) . $orig . ' '
            . Dumper::value2($type) . ' ' . Dumper::info('at position') . ' ' . $token->position
            . Dumper::bracket(')');

        Dumper::$showInfo = $info;

        return $res;
    };

    // TokenList
    Dumper::$shortObjectFormatters[TokenList::class] = static function (TokenList $tokenList): string {
        $limit = 15;
        $tokens = $tokenList->getTokens();
        $count = count($tokens);
        $contents = '';
        foreach (array_slice($tokens, 0, $limit) as $token) {
            $contents .= ctype_space($token->value) ? '·' : $token->value;
        }
        $dots = $count > $limit ? '...' : '';
        $info = Dumper::$showInfo !== true ? ' ' . Dumper::info('// #' . Dumper::objectHash($tokenList)) : '';

        return Dumper::name(get_class($tokenList)) . Dumper::bracket('(')
            . Dumper::value($contents . $dots) . ' | ' . Dumper::value2($count . ' tokens, position ' . $tokenList->getPosition())
            . Dumper::bracket(')') . $info;
    };

    Dumper::$objectFormatters[Platform::class] = static function (Platform $platform): string {
        $version = $platform->getVersion();

        return Dumper::name(get_class($platform)) . Dumper::bracket('(')
            . Dumper::value($platform->getName()) . ' ' . Dumper::value2($version->getMajorMinor() . ($version->getPatch() ? '.' . $version->getPatch() : ''))
            . Dumper::bracket(')');
    };

    ParserException::$debug = true;
}

// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable
if (!empty($_SERVER['argv'])) { // @phpstan-ignore-line ❤ empty()
    // may be running from command line, but under 'cgi-fcgi' SAPI
    header('Content-Type: text/plain');
} elseif (PHP_SAPI !== 'cli') {
    // running from browser
    Debugger::enable(Debugger::DEVELOPMENT, dirname(__DIR__) . '/log/');
}
