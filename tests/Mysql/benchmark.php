<?php

namespace SqlFtw\Tests\Mysql;

use Dogma\Application\Colors;
use Dogma\Debug\Dumper;
use function class_exists;
use function dirname;
use function in_array;

if (!class_exists(Dumper::class)) {
    require_once dirname(__DIR__, 2) . '/vendor/dogma/dogma-debug/shortcuts.php';
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/dogma/dogma-debug/shortcuts.php';
require_once __DIR__ . '/../debugger.php';

$help = in_array('help', $argv, true);
if ($help) {
    echo Colors::white("MySQL Tests usage:") . "\n";
    echo "  " . Colors::yellow("test.php") . " - runs all test suites in parallel. displays results at the end\n";
    echo "  " . Colors::yellow("test.php --single ...") . " - runs all test suites in single thread. displays results immediately\n";
    echo "  " . Colors::yellow("test.php help") . " - this help\n";
    echo "  " . Colors::yellow("test.php list") . " - list possible MySQL test suites\n";
    echo "  " . Colors::yellow("test.php <name>[, <name>]...") . " - run specified test suites\n";
    echo "  " . Colors::yellow("test.php <number>[, <number>]...") . " - run test suites specified by number\n";
    echo "  " . Colors::yellow("test.php <file1.test>[, <file2.test>]...") . " - run specified test cases\n";
    exit;
}

$listSuites = in_array('list', $argv, true);
if ($listSuites) {
    $test = new MysqlTest();
    $test->listSuites();
    exit;
}

$singleThread = in_array('--single', $argv, true);

$tests = $argv;
unset($tests[0]);
$tests = array_values(array_filter($tests, static function ($arg): bool {
    return $arg[0] !== '-';
}));

$test = new MysqlTest();
$test->benchmark($singleThread, null, $tests);
