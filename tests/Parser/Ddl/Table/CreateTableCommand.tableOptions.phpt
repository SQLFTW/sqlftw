<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());


// more
$query = 'CREATE TABLE foo (
  bar INT
) AUTO_INCREMENT 17, AVG_ROW_LENGTH 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// AUTO_INCREMENT
$query = 'CREATE TABLE foo (
  bar INT
) AUTO_INCREMENT 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// AVG_ROW_LENGTH
$query = 'CREATE TABLE foo (
  bar INT
) AVG_ROW_LENGTH 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// CHARACTER_SET
$query = "CREATE TABLE foo (
  bar INT
) CHARACTER SET 'utf8'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// CHECKSUM
$query = 'CREATE TABLE foo (
  bar INT
) CHECKSUM 0';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) CHECKSUM 1';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// COLLATE
$query = "CREATE TABLE foo (
  bar INT
) COLLATE 'ascii_general_ci'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// COMMENT
$query = "CREATE TABLE foo (
  bar INT
) COMMENT 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// COMPRESSION
$query = "CREATE TABLE foo (
  bar INT
) COMPRESSION 'ZLIB'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE TABLE foo (
  bar INT
) COMPRESSION 'LZ4'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE TABLE foo (
  bar INT
) COMPRESSION 'NONE'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// CONNECTION
$query = "CREATE TABLE foo (
  bar INT
) CONNECTION 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// DATA_DIRECTORY
$query = "CREATE TABLE foo (
  bar INT
) DATA DIRECTORY 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// DELAY_KEY_WRITE
$query = 'CREATE TABLE foo (
  bar INT
) DELAY_KEY_WRITE 0';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) DELAY_KEY_WRITE 1';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ENCRYPTION
$query = "CREATE TABLE foo (
  bar INT
) ENCRYPTION 'Y'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE TABLE foo (
  bar INT
) ENCRYPTION 'N'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ENGINE
$query = "CREATE TABLE foo (
  bar INT
) ENGINE 'InnoDB'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// INDEX_DIRECTORY
$query = "CREATE TABLE foo (
  bar INT
) INDEX DIRECTORY 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// INSERT_METHOD
$query = 'CREATE TABLE foo (
  bar INT
) INSERT_METHOD NO';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) INSERT_METHOD FIRST';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) INSERT_METHOD LAST';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// KEY_BLOCK_SIZE
$query = 'CREATE TABLE foo (
  bar INT
) KEY_BLOCK_SIZE 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// MAX_ROWS
$query = 'CREATE TABLE foo (
  bar INT
) MAX_ROWS 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// MIN_ROWS
$query = 'CREATE TABLE foo (
  bar INT
) MIN_ROWS 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// PACK_KEYS
$query = 'CREATE TABLE foo (
  bar INT
) PACK_KEYS 0';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) PACK_KEYS 1';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) PACK_KEYS DEFAULT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// PASSWORD
$query = "CREATE TABLE foo (
  bar INT
) PASSWORD 'secret'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ROW_FORMAT DEFAULT|DYNAMIC|FIXED|COMPRESSED|REDUNDANT|COMPACT
$query = 'CREATE TABLE foo (
  bar INT
) ROW_FORMAT DEFAULT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) ROW_FORMAT DYNAMIC';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) ROW_FORMAT FIXED';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) ROW_FORMAT COMPRESSED';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) ROW_FORMAT REDUNDANT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) ROW_FORMAT COMPACT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// STATS_AUTO_RECALC
$query = 'CREATE TABLE foo (
  bar INT
) STATS_AUTO_RECALC 0';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) STATS_AUTO_RECALC 1';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) STATS_AUTO_RECALC DEFAULT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// STATS_PERSISTENT
$query = 'CREATE TABLE foo (
  bar INT
) STATS_PERSISTENT 0';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) STATS_PERSISTENT 1';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'CREATE TABLE foo (
  bar INT
) STATS_PERSISTENT DEFAULT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// STATS_SAMPLE_PAGES
$query = 'CREATE TABLE foo (
  bar INT
) STATS_SAMPLE_PAGES 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// TABLESPACE
$query = "CREATE TABLE foo (
  bar INT
) TABLESPACE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// UNION
$query = 'CREATE TABLE foo (
  bar INT
) UNION (foo, bar)';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
