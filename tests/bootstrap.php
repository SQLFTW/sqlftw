<?php declare(strict_types = 1);

namespace Test;

use Dogma\Debug\Dumper;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenType;
use Tracy\Debugger;
use const PHP_SAPI;
use function class_exists;
use function dirname;
use function get_class;
use function header;
use function implode;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/nette/tester/src/bootstrap.php';
require_once __DIR__ . '/ParserHelper.php';
require_once __DIR__ . '/Assert.php';

Debugger::$maxDepth = 9;
Debugger::$strictMode = true;

if (class_exists(Dumper::class)) {
    Dumper::$intFormatters = ['~tokenType~' => static function (int $int): string {
        $types = implode('|', TokenType::getByValue($int)->getConstantNames());

        return Dumper::int((string) $int) . ' ' . Dumper::info('// TokenType::' . $types);
    }] + Dumper::$intFormatters;

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
}

// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable
if (!empty($_SERVER['argv'])) { // @phpstan-ignore-line ❤ empty()
    // may be running from command line, but under 'cgi-fcgi' SAPI
    header('Content-Type: text/plain');
} elseif (PHP_SAPI !== 'cli') {
    // running from browser
    Debugger::enable(Debugger::DEVELOPMENT, dirname(__DIR__) . '/log/');
}
