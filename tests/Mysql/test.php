<?php declare(strict_types = 1);

// phpcs:disable Squiz.Arrays.ArrayDeclaration.ValueNoNewline
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
use SqlFtw\Tests\ParserHelper;
use function dirname;
use function file_get_contents;
use function implode;
use function in_array;
use function preg_match;
use function rd;
use function rl;
use function str_replace;
use function strlen;
use function substr;

require dirname(__DIR__) . '/bootstrap.php';

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

    // too much hustle to filter out
    'func_misc.test' => ["if (!` SELECT (@sleep_time_per_result_row * @row_count - @max_acceptable_delay >\n              @sleep_time_per_result_row) AND (@row_count - 1 >= 3)`)" => 'if (XXX)'],
    //'multi_plugin_load.test' => ["if (!`select count(*) FROM INFORMATION_SCHEMA.PLUGINS\n      WHERE PLUGIN_NAME='qa_auth_server'\n      and PLUGIN_LIBRARY LIKE 'qa_auth_server%'`)" => 'if (XXX)'],
    //'audit_plugin.test' => ["if(`SELECT CONVERT(@@version_compile_os USING latin1)\n           IN (\"Win32\",\"Win64\",\"Windows\")`)" => 'if (XXX)'],
    //'audit_plugin_2.test' => ["if(`SELECT CONVERT(@@version_compile_os USING latin1)\n           IN (\"Win32\",\"Win64\",\"Windows\")`)" => 'if (XXX)'],
    //'audit_plugin_bugs.test' => ["if(`SELECT CONVERT(@@version_compile_os USING latin1)\n           IN (\"Win32\",\"Win64\",\"Windows\")`)" => 'if (XXX)'],
    //'ndb_one_fragment.test' => ["if (`select max(used_pages) > 1.15 * @data_memory_pages\n       from ndbinfo.memoryusage where memory_type = 'Data memory'`)" => 'if (XXX)'],

    // invalid "," before ENGINE
    'ddl_rewriter.test' => ["PARTITION p1 VALUES IN (1) DATA DIRECTORY = '/tmp' ,ENGINE = InnoDB" => "PARTITION p1 VALUES IN (1) DATA DIRECTORY = '/tmp' ENGINE = InnoDB"],

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

$knownFailures = [
    'create table t1 (t1.index int)', // qualified column name
    'create table t1(t1.name int)', // qualified column name
    'create table t2(test.t2.name int)', // qualified column name
    'REVOKE system_user_role ON *.* FROM non_sys_user', // "REVOKE <role>" has no "ON"
    'create index on edges (s)', // index without a name
    'create index on edges (e)', // index without a name
    'CREATE INDEX ON t1(a)', // index without a name
    'CREATE INDEX ON t1 (col1, col2)', // index without a name
    // "rows" is already a reserved word
    "--echo the result rows with missed equal to NULL should count all rows (160000)\n--echo the other rows are the failed lookups and there should not be any such\nselect if(isnull(t1.a),t2.a,NULL) missed, count(*) rows from t2 left join t1 on t1.a=t2.a group by if(isnull(t1.a),t2.a,NULL)",
    "--echo the left join below should result in scanning t2 and do pk lookups in t1\n--replace_column 10 # 11 #\nexplain select if(isnull(t1.a),t2.a,NULL) missed, count(*) rows from t2 left join t1 on t1.a=t2.a group by if(isnull(t1.a),t2.a,NULL)",
    'else { } DROP TABLE t2', // todo: tests
    "-- X   --error ER_NO_SYSTEM_TABLE_ACCESS\n  CREATE PROCEDURE ddse_access() DROP TABLE mysql.innodb_index_stats(i INTEGER)", // no idea what the () on end means
];

$parser = ParserHelper::getParserFactory(Platform::MYSQL, '8.0.29')->getParser();
$settings = $parser->getSettings();

//$only = 'innodb_bug48024.test';

$dir = dirname(__DIR__, 3) . '/mysql-server/mysql-test';
//$dir = dirname(__DIR__, 3) . '/mysql-server/mysql-test/t';

$it = new RecursiveDirectoryIterator($dir);
$it = new RecursiveIteratorIterator($it);

$count = 0;
$size = 0;

echo "\n";

/** @var SplFileInfo $fileInfo */
foreach ($it as $fileInfo) {
    if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'test') {
        continue;
    }
    $path = str_replace('\\', '/', $fileInfo->getPathname());
    if (isset($only) && !Str::endsWith($path, $only)) {
        continue;
    }
    foreach ($skips as $skip) {
        if (Str::contains($path, $skip)) {
            //rl('SKIPPED: ' . $path);
            continue 2;
        }
    }

    rl($path, null, 'g');

    $contents = (string) file_get_contents($fileInfo->getPathname());
    $contents = str_replace("\r\n", "\n", $contents);

    foreach ($replacements as $file => $repl) {
        if (Str::endsWith($path, $file)) {
            $contents = Str::replaceKeys($contents, $repl);
        }
    }

    $contents = MysqlTestFilter::filter($contents);
    //continue;

    // reset settings
    $settings->setMode($settings->getPlatform()->getDefaultMode());
    $settings->setDelimiter(';');

    $count++;
    $size += strlen($contents);

    $formatter = new Formatter($settings);
    $after = static function (TokenList $tokenList, array $commands) use ($formatter): void {
        Assert::same($tokenList->serialize(), implode("\n", array_map(static function (Command $command) use ($formatter): string {
            return $command->serialize($formatter);
        }, $commands)));
    };

    Assert::validCommands($contents, $parser, $formatter, static function (InvalidCommand $command, string $sql) use ($count, $path, $knownFailures): bool
    {
        $statement = $command->getTokenList()->serialize();
        $comments = $command->getCommentsBefore();
        $lastComment = end($comments);
        if (in_array($statement, $knownFailures, true)) {
            return false;
        } elseif ($lastComment !== false && Str::startsWith($lastComment, '-- error')) {
            return false;
        } elseif ($statement[0] === '}' || Str::endsWith($statement, '}')) {
            return false;
        }

        rl($count . ': ' . Str::after($path, 'mysql-test'));
        rd($command->getTokenList());
        rd($statement);
        rd($comments);
        $firstToken = $command->getTokenList()->getTokens()[0];
        rd(substr($sql, $firstToken->position - 100, 200));

        return true;
    });
    echo '.';
    if ($count > 500) {
        //break;
    }
}

rl('Count: ' . $count);
rl('Size: ' . Units::memory($size));
