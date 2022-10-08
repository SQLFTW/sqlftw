<?php declare(strict_types = 1);

// spell-check-ignore: DET abcdefghijklmnopqrstuvwxyz charlength crc doesn usexxx barp2 foobarp1 mgm qa abc blabla
// spell-check-ignore: repl wp xplugin FC ddse memoryusage notembedded storedproc

namespace SqlFtw\Tests\Mysql;

use Dogma\Debug\Dumper;
use Dogma\Debug\Units;
use Dogma\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;
use function class_exists;
use function dirname;
use function function_exists;
use function preg_replace;
use function rd;
use function rl;
use function set_time_limit;
use function str_replace;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/Errors.php';
require_once __DIR__ . '/Failures.php';
require_once __DIR__ . '/NonFailures.php';
if (class_exists(Dumper::class)) {
    require_once __DIR__ . '/../debugger.php';
}

ini_set('memory_limit', '4G');

$skips = [
    // todo: problems with �\' in shift-jis encoding. need to tokenize strings per-character not per-byte
    'jp_alter_sjis.test',
    'jp_charlength_sjis.test',
    'jp_create_db_sjis.test',
    'jp_create_tbl_sjis.test',
    'jp_enum_sjis.test',
    'jp_insert_sjis.test',
    'jp_instr_sjis.test',
    'jp_join_sjis.test',
    'jp_left_sjis.test',
    'jp_length_sjis.test',
    'jp_like_sjis.test',
    'jp_locate_sjis.test',
    'jp_lpad_sjis.test',
    'jp_ltrim_sjis.test',
    'jp_ps_sjis.test',
    'jp_replace_sjis.test',
    'jp_reverse_sjis.test',
    'jp_right_sjis.test',
    'jp_rpad_sjis.test',
    'jp_rtrim_sjis.test',
    'jp_subquery_sjis.test',
    'jp_substring_sjis.test',
    'jp_update_sjis.test',
    'jp_where_sjis.test',
    'ctype_sjis.test',

    // todo: problems with gb18030 encoding. need to tokenize strings per-character not per-byte
    'ctype_gb18030_encoding_cn.test',

    // won't fix - invalid combination of SQL and Perl comment directives
    'binlog_start_comment.test',

    // need to parse some special syntax in string argument
    'debug_sync.test',

    // Heatwave analytics plugin
    'secondary_engine',

    // this test for a bug contains another bug. we are implementing correct behavior (nested comments)
    'innodb_bug48024.test',

    // badly named result file
    'r/server_offline_7.test',

    // no significant SQL to test, but problems...
    'suite/stress/t/wrapper.test',
    'binlog_expire_warnings.test',
    'binlog_gtid_accessible_table_with_readonly.test',
    'mysqltest.test',
    'charset_master.test',
];

$dir = dirname(__DIR__, 3) . '/mysql-server/mysql-test';
$it = new RecursiveDirectoryIterator($dir);
$it = new RecursiveIteratorIterator($it);

echo "\n";

$paths = [];
/** @var SplFileInfo $fileInfo */
foreach ($it as $fileInfo) {
    if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'test') {
        continue;
    }
    $path = str_replace('\\', '/', $fileInfo->getPathname());

    if (isset($only) && !Str::endsWith($path, $only)) { // @phpstan-ignore-line (debug code)
        continue;
    }
    foreach ($skips as $skip) {
        if (Str::contains($path, $skip)) {
            continue 2;
        }
    }

    $paths[] = $path;
}


$process = static function (string $path): Result {
    ini_set('memory_limit', '4G');
    set_time_limit(15);
    if (function_exists('memory_reset_peak_usage')) {
        memory_reset_peak_usage(); // 8.2
    }

    return MysqlTest::run($path);
};

$results = wait(parallelMap($paths, $process));

$size = $time = $statements = $tokens = 0;
$fails = [];
$nonFails = [];
/** @var Result $result */
foreach ($results as $result) {
    $size += $result->size;
    $time += $result->time;
    $statements += $result->statements;
    $tokens += $result->tokens;
    if ($result->fails !== []) {
        $fails[$result->path] = $result->fails;
    }
    if ($result->nonFails !== []) {
        $nonFails[$result->path] = $result->nonFails;
    }
}

$platform = Platform::get(Platform::MYSQL, '8.0.29');
$session = new Session($platform);
$formatter = new Formatter($session);

if ($fails !== []) {
    rl('Should not fail:', null, 'r');
}
foreach ($fails as $path => $fail) {
    rl($path, null, 'r');
    foreach ($fail as [$command, $tokenList]) {
        $commandSerialized = $formatter->serialize($command);
        $commandSerialized = preg_replace('~\s+~', ' ', $commandSerialized);
        rl($commandSerialized);

        $tokensSerialized = trim($tokenList->serialize());
        rl($tokensSerialized, null, 'y');

        rd($tokenList);
    }
}
if ($nonFails !== []) {
    rl('Should not fail:', null, 'r');
}
foreach ($nonFails as $path => $nonFail) {
    rl($path, null, 'r');
    foreach ($nonFail as [$command, $tokenList]) {
        $tokensSerialized = trim($tokenList->serialize());
        rl($tokensSerialized, null, 'y');

        $commandSerialized = $formatter->serialize($command);
        $commandSerialized = preg_replace('~\s+~', ' ', $commandSerialized);
        rl($commandSerialized);

        rd($tokenList);
    }
}

rl('Files: ' . count($paths));
rl('Size: ' . Units::memory($size));
rl('Time: ' . Units::time($time));
rl('Statements: ' . $statements);
rl('Tokens: ' . $tokens);

usort($results, static function (Result $a, Result $b) {
    return $b->time <=> $a->time;
});

rl('Slowest: ');
$n = 0;
foreach ($results as $result) {
    $t = Units::time($result->time);
    $m = Units::memory($result->memory);
    $s = Units::memory($result->size);
    rl("  {$t}, {$m}, {$result->statements} st ({$result->path} - {$s})");
    $n++;
    if ($n >= 10) {
        break;
    }
}
