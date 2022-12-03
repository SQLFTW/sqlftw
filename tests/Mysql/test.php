<?php declare(strict_types = 1);

namespace SqlFtw\Tests\Mysql;

use Dogma\Debug\Dumper;
use function class_exists;

require_once __DIR__ . '/../../vendor/autoload.php';
if (class_exists(Dumper::class)) {
    require_once __DIR__ . '/../debugger.php';
}

$singleThread = in_array('--single', $argv, true);

$test = new MysqlTest();
$test->run($singleThread);

//MysqlTest::run($singleThread);
