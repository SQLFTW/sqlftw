<?php

namespace Test;

use Dogma\Debug\Dumper;
use Tracy\Debugger;
use function class_exists;
use function dirname;
use function header;
use const PHP_SAPI;

if (!class_exists(Dumper::class)) {
    require_once dirname(__DIR__) . '/vendor/dogma/dogma-debug/shortcuts.php';
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/nette/tester/src/bootstrap.php';
require_once __DIR__ . '/ParserHelper.php';
require_once __DIR__ . '/Assert.php';
require_once __DIR__ . '/debugger.php';

// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable
if (!empty($_SERVER['argv'])) { // @phpstan-ignore-line ❤ empty()
    // may be running from command line, but under 'cgi-fcgi' SAPI
    header('Content-Type: text/plain');
} elseif (PHP_SAPI !== 'cli') {
    // running from browser
    Debugger::enable(Debugger::DEVELOPMENT, dirname(__DIR__) . '/log/');
}
