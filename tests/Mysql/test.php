<?php declare(strict_types = 1);

// spell-check-ignore: DET abcdefghijklmnopqrstuvwxyz charlength crc doesn usexxx barp2 foobarp1 mgm qa abc blabla
// spell-check-ignore: repl wp xplugin FC ddse memoryusage notembedded storedproc

namespace SqlFtw\Tests\Mysql;

use Dogma\Debug\Units;
use Dogma\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\InvalidCommand;
use SqlFtw\Parser\TokenList;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Command;
use SqlFtw\Tests\Assert;
use SqlFtw\Tests\MysqlTestAssert;
use SqlFtw\Tests\ParserHelper;
use Throwable;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function rd;
use function rl;
use function str_replace;
use function strlen;
use function substr;

require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/Errors.php';
require __DIR__ . '/Failures.php';
require __DIR__ . '/NonFailures.php';

ini_set('memory_limit', (string) (4 * 1024 * 1024 * 1024));

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

    // todo: false positives
    //'year_engine.test',

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

$replacements = [
    // some test fixtures
    '_alter.test' => ['#DET#' => ''],
    'gr_xplugin_global_variables.test' => ['%XCOM_PORT%' => '1234'],
    'undo_tablespace_win.test' => ["use Win32API" => "usexxx Win32API"],

    // unrecognized error test
    'mysqltest.test' => ["SELECT * FROM nowhere else;" => "SELECT * FROM nowhere;"],
    'storedproc.test' => [
        "set @@sql_mode = 'ansi, error_for_division_by_zero';" => "--error ER_\nset @@sql_mode = 'ansi, error_for_division_by_zero';",
        "DROP PROCEDURE IF EXISTSsp1;" => "DROP PROCEDURE IF EXISTS sp1;",
    ],
    'events_2.test' => [
        'end|                                                                                                                                                    --error ER_EVENT_RECURSION_FORBIDDEN' => "end|\n--error ER_EVENT_RECURSION_FORBIDDEN",
    ],

    // non-existent compression algorithm
    'table_compress.test' => ['zlibX' => 'zlib', 'abcdefghijklmnopqrstuvwxyz' => 'zlib'],
    'innodb_bug30899683.test' => ['zlibX' => 'zlib'],

    // non-existent privileges
    'grant4.test' => ['REVOKE abc ON' => 'REVOKE SELECT ON'],
    'roles.test' => [
        'REVOKE engineering ON' => 'REVOKE SELECT ON',
        'REVOKE wp_administrators, engineering ON' => 'REVOKE SELECT ON',
    ],

    // broken error checks
    'gcol_insert_ignore.test' => [
        "error 0\n      ,ER_BLOB_KEY_WITHOUT_LENGTH\n      ,ER_UNSUPPORTED_ACTION_ON_GENERATED_COLUMN\n      ,ER_JSON_USED_AS_KEY\n    ;" => "error 0,ER_BLOB_KEY_WITHOUT_LENGTH,ER_UNSUPPORTED_ACTION_ON_GENERATED_COLUMN,ER_JSON_USED_AS_KEY;",
        "error 0\n      ,ER_WRONG_SUB_KEY\n      ,ER_UNSUPPORTED_ACTION_ON_GENERATED_COLUMN\n      ,ER_JSON_USED_AS_KEY\n    ;" => "error 0,ER_WRONG_SUB_KEY,ER_UNSUPPORTED_ACTION_ON_GENERATED_COLUMN,ER_JSON_USED_AS_KEY;",
        "error 0\n      ,ER_INVALID_JSON_TEXT\n      ,ER_INVALID_JSON_CHARSET\n      ,ER_CANT_CREATE_GEOMETRY_OBJECT\n    ;" => "error 0,ER_INVALID_JSON_TEXT,ER_INVALID_JSON_CHARSET,ER_CANT_CREATE_GEOMETRY_OBJECT;",
        "error 0\n      ,ER_INVALID_JSON_VALUE_FOR_CAST\n      ,ER_TRUNCATED_WRONG_VALUE_FOR_FIELD\n      ,ER_TRUNCATED_WRONG_VALUE\n    ;" => "error 0,ER_INVALID_JSON_VALUE_FOR_CAST,ER_TRUNCATED_WRONG_VALUE_FOR_FIELD,ER_TRUNCATED_WRONG_VALUE;",
    ],

    // broken strings
    'innochecksum.test' => [
        "doesn't" => 'does not',
        "Error while setting value \'strict_innodb\' to \'strict-check\'" => "Error while setting value strict_innodb to strict-check",
        "Error while setting value \'strict_crc32\' to \'strict-check\'" => "Error while setting value strict_crc32 to strict-check",
        "Error while setting value \'strict_none\' to \'strict-check\'" => "Error while setting value strict_none to strict-check",
        "Error while setting value \'InnoBD\' to \'strict-check\'" => "Error while setting value InnoBD to strict-check",
        "Error while setting value \'crc\' to \'strict-check\'" => "Error while setting value crc to strict-check",
        "Error while setting value \'no\' to \'strict-check\'" => "Error while setting value no to strict-check",
        "Error while setting value \'strict_crc32\' to \'write\'" => "Error while setting value strict_crc32 to write",
        "Error while setting value \'strict_innodb\' to \'write\'" => "Error while setting value strict_innodb to write",
        "Error while setting value \'crc23\' to \'write\'" => "Error while setting value crc23 to write",
    ],
    'log_encrypt_kill.test' => ["let SEARCH_PATTERN=Can\\'t" => 'let SEARCH_PATTERN=Cant'],
    'array_index.test' => ["Lookups of single SON null value can't use index" => 'Lookups of single SON null value cant use index'],
    'ndb_rpl_conflict_epoch2.test' => ['\\"' => '"'],
    'ndb_rpl_conflict_epoch2_trans.test' => ['\\"' => '"'],

    // broken delimiters
    'json_no_table.test' => ['execute s1 using @x # OK - returns ["a", "b"] and ["c", "a"];' => 'execute s1 using @x; # OK - returns ["a", "b"] and ["c", "a"]'],
    'ndb_alter_table_column_online.test' => ["name like '%t1%'#and type like '%UserTable%';" => "name like '%t1%';#and type like '%UserTable%'"],
    'rpl_temporary.test' => ['insert into t1 select * from `\E4\F6\FC\C4\D6\DC`' => 'insert into t1 select * from `\E4\F6\FC\C4\D6\DC`;'],
    'rpl_user_variables.test' => ['CREATE FUNCTION f1() RETURNS INT RETURN @a; DELIMITER |; CREATE' => "CREATE FUNCTION f1() RETURNS INT RETURN @a;\nDELIMITER |;\nCREATE"],
    'rpl_multi_source_cmd_errors.test' => ['START SLAVE UNTIL SOURCE_LOG_FILE = "dummy-bin.0000001", SOURCE_LOG_POS = 1729' => 'START SLAVE UNTIL SOURCE_LOG_FILE = "dummy-bin.0000001", SOURCE_LOG_POS = 1729;'],

    // too much hustle to filter out
    'func_misc.test' => ["if (!` SELECT (@sleep_time_per_result_row * @row_count - @max_acceptable_delay >\n              @sleep_time_per_result_row) AND (@row_count - 1 >= 3)`)" => 'if (XXX)'],
    //'multi_plugin_load.test' => ["if (!`select count(*) FROM INFORMATION_SCHEMA.PLUGINS\n      WHERE PLUGIN_NAME='qa_auth_server'\n      and PLUGIN_LIBRARY LIKE 'qa_auth_server%'`)" => 'if (XXX)'],
    //'audit_plugin.test' => ["if(`SELECT CONVERT(@@version_compile_os USING latin1)\n           IN (\"Win32\",\"Win64\",\"Windows\")`)" => 'if (XXX)'],
    //'audit_plugin_2.test' => ["if(`SELECT CONVERT(@@version_compile_os USING latin1)\n           IN (\"Win32\",\"Win64\",\"Windows\")`)" => 'if (XXX)'],
    //'audit_plugin_bugs.test' => ["if(`SELECT CONVERT(@@version_compile_os USING latin1)\n           IN (\"Win32\",\"Win64\",\"Windows\")`)" => 'if (XXX)'],
    //'ndb_one_fragment.test' => ["if (`select max(used_pages) > 1.15 * @data_memory_pages\n       from ndbinfo.memoryusage where memory_type = 'Data memory'`)" => 'if (XXX)'],

    // invalid "," before ENGINE
    'ddl_rewriter.test' => ["PARTITION p1 VALUES IN (1) DATA DIRECTORY = '/tmp' ,ENGINE = InnoDB" => "PARTITION p1 VALUES IN (1) DATA DIRECTORY = '/tmp' ENGINE = InnoDB"],

    // fucking includes :E
    'ndb_native_default_support.test' => ['--source suite/ndb/include/turn_off_strict_sql_mode.inc' => "set sql_mode=(select replace(@@sql_mode,'STRICT_TRANS_TABLES',''));"],
    'ndb_replace.test' => ['--source suite/ndb/include/turn_off_strict_sql_mode.inc' => "set sql_mode=(select replace(@@sql_mode,'STRICT_TRANS_TABLES',''));"],
    'ndb_restore_conv_lossy_charbinary.test' => ['--source suite/ndb/include/turn_off_strict_sql_mode.inc' => "set sql_mode=(select replace(@@sql_mode,'STRICT_TRANS_TABLES',''));"],
    'ndb_restore_conv_lossy_integral.test' => ['--source suite/ndb/include/turn_off_strict_sql_mode.inc' => "set sql_mode=(select replace(@@sql_mode,'STRICT_TRANS_TABLES',''));"],
    'ndb_restore_conv_padding.test' => ['--source suite/ndb/include/turn_off_strict_sql_mode.inc' => "set sql_mode=(select replace(@@sql_mode,'STRICT_TRANS_TABLES',''));"],
    'ndb_row_format.test' => ['--source suite/ndb/include/turn_off_strict_sql_mode.inc' => "set sql_mode=(select replace(@@sql_mode,'STRICT_TRANS_TABLES',''));"],
    'ndb_update_no_read.test' => ['--source suite/ndb/include/turn_off_strict_sql_mode.inc' => "set sql_mode=(select replace(@@sql_mode,'STRICT_TRANS_TABLES',''));"],

    // names concatenation in ANSI mode
    'parser.test' => [
        // todo: string concatenation in ansi_strings mode? are you kidding me?
        'select instr("foobar" "p1", "bar");' => 'select instr("foobarp1", "bar");',
        'select instr("foobar", "bar" "p2");' => 'select instr("foobar", "barp2");',
        // needed to switch sql_mode
        "SET sql_mode=(SELECT CONCAT(@@sql_mode, ',PIPES_AS_CONCAT'));" => "SET sql_mode=sys.list_add(@@sql_mode, 'PIPES_AS_CONCAT');",
    ],

    // emulating variables needed for this: SET @@sql_mode= @org_mode;
    'sql_mode.test' => ["SELECT '\''; # restore Emacs SQL mode font lock sanity" => "SELECT ''; # restore Emacs SQL mode font lock sanity"],
];

$parser = ParserHelper::getParserFactory(Platform::MYSQL, '8.0.29')->getParser();
$session = $parser->getSession();
$formatter = new Formatter($session);
$filter = new MysqlTestFilter();

//$only = 'rpl_temporary.test';

$dir = dirname(__DIR__, 3) . '/mysql-server/mysql-test';
//$dir = dirname(__DIR__, 3) . '/mysql-server/mysql-test/suite/test_services';
$lastFailPath = __DIR__ . '/last-fail.txt';

$it = new RecursiveDirectoryIterator($dir);
$it = new RecursiveIteratorIterator($it);

$files = 0;
$size = 0;
$queries = 0;

echo "\n";

/** @var SplFileInfo $fileInfo */
foreach ($it as $fileInfo) {
    if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'test') {
        continue;
    }
    $path = str_replace('\\', '/', $fileInfo->getPathname());
    if (!isset($only) && file_exists($lastFailPath)) { // @phpstan-ignore-line (debug code)
        $only = file_get_contents($lastFailPath);
        if ($only === '') {
            unset($only);
        }
    }
    if (isset($only) && !Str::endsWith($path, $only)) { // @phpstan-ignore-line (debug code)
        continue;
    }
    foreach ($skips as $skip) {
        if (Str::contains($path, $skip)) {
            //rl('SKIPPED: ' . $path);
            continue 2;
        }
    }
    // skip directories
    if (strpos($path, '/suite/') !== false) {
        //continue;
    }

    $files++;
    rl($path, (string) $files, 'g');
    //rm();

    $contents = (string) file_get_contents($fileInfo->getPathname());
    $contents = str_replace("\r\n", "\n", $contents);

    foreach ($replacements as $file => $repl) {
        if (Str::endsWith($path, $file)) {
            $contents = Str::replaceKeys($contents, $repl);
        }
    }

    $contents = $filter->filter($contents);
    //continue;

    // reset settings
    $session->reset();

    $size += strlen($contents);

    try {
        $queries += MysqlTestAssert::validCommands($contents, $parser, $formatter, static function (
            string $sql,
            InvalidCommand $command,
            TokenList $tokenList
        ): bool {
            $firstToken = $tokenList->getTokens()[0];
            rd(substr($sql, $firstToken->position - 100, 200));

            return true;
        });
    } catch (Throwable $e) {
        file_put_contents($lastFailPath, $path);
        throw $e;
    }

    file_put_contents($lastFailPath, '');

    echo '.';
    if ($files >= 100000000) {
        break;
    }
}

rl('Files: ' . $files);
rl('Size: ' . Units::memory($size));
rl('Queries: ' . $queries);
