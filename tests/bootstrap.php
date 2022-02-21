<?php declare(strict_types = 1);

namespace Test;

use Tracy\Debugger;
use const PHP_SAPI;
use function dirname;
use function header;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/nette/tester/src/bootstrap.php';
require_once __DIR__ . '/Assert.php';

Debugger::$maxDepth = 9;
Debugger::$strictMode = true;

// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable
if (!empty($_SERVER['argv'])) { // @phpstan-ignore-line ❤ empty()
    // may be running from command line, but under 'cgi-fcgi' SAPI
    header('Content-Type: text/plain');
} elseif (PHP_SAPI !== 'cli') {
    // running from browser
    Debugger::enable(Debugger::DEVELOPMENT, dirname(__DIR__) . '/log/');
}
