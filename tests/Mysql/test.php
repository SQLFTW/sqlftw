<?php declare(strict_types = 1);

namespace SqlFtw\Tests\Mysql;

use Dogma\Debug\Dumper;
use function class_exists;
use function in_array;

require_once __DIR__ . '/../../vendor/autoload.php';
if (class_exists(Dumper::class)) {
    require_once __DIR__ . '/../debugger.php';
}

rd($argv);

$singleThread = in_array('--single', $argv, true);

$tests = $argv;
unset($tests[0]);
$tests = array_values(array_filter($tests, static function ($arg): bool {
    return $arg[0] !== '-';
}));

$test = new MysqlTest();
$test->run($singleThread, null, $tests);
