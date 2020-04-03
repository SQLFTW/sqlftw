<?php declare(strict_types = 1);

namespace Test;

use Tracy\Debugger;
use const PHP_SAPI;
use function dirname;
use function header;

// something fucks up cwd in Tester on PHP 7.4 and extensions fail to load in tests
if (!extension_loaded('sockets')) {
    chdir('c:/tools/php74');
    dl('php_sockets.dll');
    dl('php_mbstring.dll');
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/nette/tester/src/bootstrap.php';
require_once __DIR__ . '/Assert.php';

require_once __DIR__ . '/../vendor/dogma/dogma-dev/src/debug-client.php';

Debugger::$maxDepth = 9;
Debugger::$strictMode = true;

// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable
if (!empty($_SERVER['argv'])) {
    // may be running from command line, but under 'cgi-fcgi' SAPI
    header('Content-Type: text/plain');
} elseif (PHP_SAPI !== 'cli') {
    // running from browser
    Debugger::enable(Debugger::DEVELOPMENT, dirname(__DIR__) . '/log/');
}
