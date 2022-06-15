<?php declare(strict_types = 1);

// phpcs:disable Squiz.Arrays.ArrayDeclaration.ValueNoNewline
// spell-check-ignore: DET abcdefghijklmnopqrstuvwxyz charlength crc doesn usexxx barp2 dbug foobarp1 mgm qa abc blabla repl wp xplugin

namespace SqlFtw\Tests\Mysql;

use Dogma\Debug\Units;
use Dogma\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\Lexer;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Command;
use SqlFtw\Tests\Assert;
use SqlFtw\Tests\ParserHelper;
use function array_merge;
use function array_slice;
use function dirname;
use function file_get_contents;
use function implode;
use function in_array;
use function preg_match;
use function rl;
use function rt;
use function str_replace;
use function strlen;
use function strtolower;

require dirname(__DIR__) . '/bootstrap.php';

ini_set('memory_limit', '256MB');

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

    // badly named result file
    'r/server_offline_7.test',

    // no SQL
    'suite/stress/t/wrapper.test',
];

$mysqlTestSuiteArtifacts = array_merge(Lexer::MYSQL_TEST_SUITE_COMMANDS, ['$file', '$match', '$val', '.', '[', '}', '<', '1', '111']);

$multiStatementFiles = [
    'rpl_multi_query', 'rpl_sp', 'audit_plugin_2', 'innodb_bug48024', 'ndb_sp', 'nesting', 'prepared_stmts_by_stored_programs',
    'rpl_events', 'rpl_bug31076', 'func_time', 'metadata', 'multi_statement', 'parser', 'partition', 'session_tracker_trx_state_myisam',
    'signal', 'sp-security', 'sp-ucs2', 'sp', 'sp_trans_myisam', 'trigger', 'view',
];
$multiStatementRegexp = '~(' . implode('|', $multiStatementFiles) . ')\\.test$~';

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

    // invalid "," before ENGINE
    'ddl_rewriter.test' => ["PARTITION p1 VALUES IN (1) DATA DIRECTORY = '/tmp' ,ENGINE = InnoDB" => "PARTITION p1 VALUES IN (1) DATA DIRECTORY = '/tmp' ENGINE = InnoDB"],

    // fuck nested comments. not even touching this
    'comments.test' => ["/* */ */" => "*/"],
    'rpl_stm_create_if_not_exists.test' => ['/*blabla*/ ' => ''],
    'partition_mgm.test' => ["/* Test\n   of multi-line\n   comment */" => ''],

    // names concatenation in ANSI mode
    'parser.test' => [
        // todo: string concatenation in ansi_strings mode? are you kidding me?
        'select instr("foobar" "p1", "bar");' => 'select instr("foobarp1", "bar");',
        'select instr("foobar", "bar" "p2");' => 'select instr("foobar", "barp2");',
        // needed to switch sql_mode
        "SET sql_mode=(SELECT CONCAT(@@sql_mode, ',PIPES_AS_CONCAT'));" => "SET sql_mode=sys.list_add(@@sql_mode, 'PIPES_AS_CONCAT');",
    ],

    // not valid in later versions
    'create.test' => [
        'create table t1 (t1.index int);' => 'create table t1 (t1index int);',
        'create table t1(t1.name int);' => 'create table t1(t1name int);',
        'create table t2(test.t2.name int);' => 'create table t2(testt2name int);',
    ],
];

$parser = ParserHelper::getParserFactory(Platform::MYSQL, '8.0.0')->getParser();
$settings = $parser->getSettings();
$settings->mysqlTestMode = true;

//$only = 'derived_condition_pushdown.test';

$dir = dirname(__DIR__, 3) . '/mysql-server/mysql-test';
//$dir = dirname(__DIR__, 3) . '/mysql-server/mysql-test/t';

$it = new RecursiveDirectoryIterator($dir);
$it = new RecursiveIteratorIterator($it);

$count = 0;
$size = 0;

rt();
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
            rl('SKIPPED: ' . $path);
            continue 2;
        }
    }

    rl(Str::after($path, 'mysql-test'));
    $contents = (string) file_get_contents($fileInfo->getPathname());
    $contents = str_replace("\r\n", "\n", $contents);
    foreach ($replacements as $file => $repl) {
        if (Str::endsWith($path, $file)) {
            $contents = Str::replaceKeys($contents, $repl);
        }
    }

    // reset settings
    $settings->setMode($settings->getPlatform()->getDefaultMode());
    $settings->setDelimiter(';');
    $settings->setMultiStatements(false);
    if (preg_match($multiStatementRegexp, $path) !== 0) {
        // multi-statements
        rl('multi-statements on');
        $settings->setMultiStatements(true);
    }

    $count++;
    $size += strlen($contents);

    $before = static function (TokenList $tokenList) use ($mysqlTestSuiteArtifacts): ?TokenList {
        $tokens = $tokenList->getTokens();
        do {
            $count = count($tokens);
            $first = $tokenList->getFirstSignificantToken();
            if ($first === null) {
                return null;
            }
            $value = strtolower($first->value);
            if (in_array($value, $mysqlTestSuiteArtifacts, true)) {
                foreach ($tokens as $i => $token) {
                    if (($token->type & TokenType::TEST_CODE) !== 0 && $token->value === 'EOF') {
                        $tokens = array_slice($tokens, $i + 1);
                        if ($tokens === []) {
                            return null;
                        }
                        break;
                    }
                }
                return null;
            }
        } while (count($tokens) !== $count);

        // for testing serialization
        // remove comments and perl, replace whitespace with single space and \n after delimiter
        /*$previous = new Token(0, 0, '');
        foreach ($tokens as $i => $token) {
            if (($token->type & (TokenType::TEST_CODE | TokenType::COMMENT)) !== 0) {
                unset($tokens[$i]);
            } elseif (($token->type & TokenType::WHITESPACE) !== 0) {
                if (($previous->type & TokenType::SYMBOL) !== 0 && $previous->value === ';') {
                    $tokens[$i] = $previous = new Token(TokenType::WHITESPACE, -1, "\n");
                } elseif (($previous->type & (TokenType::DELIMITER || TokenType::DELIMITER_DEFINITION)) !== 0) {
                    $tokens[$i] = $previous = new Token(TokenType::WHITESPACE, -1, "\n");
                } elseif (($previous->type & TokenType::WHITESPACE) !== 0) {
                    unset($tokens[$i]);
                } else {
                    $tokens[$i] = $previous = new Token(TokenType::WHITESPACE, -1, ' ');
                }
            } else {
                $previous = $token;
            }
        }*/

        if ($tokens === []) {
            return null;
        }

        return new TokenList(array_values($tokens), $tokenList->getSettings());
    };

    $after = null;
    /*$formatter = new Formatter($settings);
    $after = static function (TokenList $tokenList, array $commands) use ($formatter): void {
        Assert::same($tokenList->serialize(), implode("\n", array_map(static function (Command $command) use ($formatter): string {
            return $command->serialize($formatter);
        }, $commands)));
    };*/

    Assert::validCommands($contents, $parser, $before, $after);
    rt();
}

rl('Count: ' . $count);
rl('Size: ' . Units::memory($size));
