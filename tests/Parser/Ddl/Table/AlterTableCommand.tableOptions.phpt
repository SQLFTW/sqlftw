<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// AUTO_INCREMENT
$query = 'ALTER TABLE foo
  AUTO_INCREMENT 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// AVG_ROW_LENGTH
$query = 'ALTER TABLE foo
  AVG_ROW_LENGTH 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// CHARACTER_SET
$query = "ALTER TABLE foo
  CHARACTER SET 'utf8'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// CHECKSUM
$query = 'ALTER TABLE foo
  CHECKSUM 0';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  CHECKSUM 1';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// COLLATE
$query = "ALTER TABLE foo
  COLLATE 'ascii_general_ci'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// COMMENT
$query = "ALTER TABLE foo
  COMMENT 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// COMPRESSION
$query = "ALTER TABLE foo
  COMPRESSION 'ZLIB'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER TABLE foo
  COMPRESSION 'LZ4'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER TABLE foo
  COMPRESSION 'NONE'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// CONNECTION
$query = "ALTER TABLE foo
  CONNECTION 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// DATA_DIRECTORY
$query = "ALTER TABLE foo
  DATA DIRECTORY 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// DELAY_KEY_WRITE
$query = 'ALTER TABLE foo
  DELAY_KEY_WRITE 0';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  DELAY_KEY_WRITE 1';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ENCRYPTION
$query = "ALTER TABLE foo
  ENCRYPTION 'Y'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER TABLE foo
  ENCRYPTION 'N'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ENGINE
$query = "ALTER TABLE foo
  ENGINE 'InnoDB'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// INDEX_DIRECTORY
$query = "ALTER TABLE foo
  INDEX DIRECTORY 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// INSERT_METHOD
$query = 'ALTER TABLE foo
  INSERT_METHOD NO';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  INSERT_METHOD FIRST';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  INSERT_METHOD LAST';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// KEY_BLOCK_SIZE
$query = 'ALTER TABLE foo
  KEY_BLOCK_SIZE 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// MAX_ROWS
$query = 'ALTER TABLE foo
  MAX_ROWS 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// MIN_ROWS
$query = 'ALTER TABLE foo
  MIN_ROWS 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// PACK_KEYS
$query = 'ALTER TABLE foo
  PACK_KEYS 0';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  PACK_KEYS 1';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  PACK_KEYS DEFAULT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// PASSWORD
$query = "ALTER TABLE foo
  PASSWORD 'secret'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ROW_FORMAT DEFAULT|DYNAMIC|FIXED|COMPRESSED|REDUNDANT|COMPACT
$query = 'ALTER TABLE foo
  ROW_FORMAT DEFAULT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ROW_FORMAT DYNAMIC';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ROW_FORMAT FIXED';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ROW_FORMAT COMPRESSED';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ROW_FORMAT REDUNDANT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ROW_FORMAT COMPACT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// STATS_AUTO_RECALC
$query = 'ALTER TABLE foo
  STATS_AUTO_RECALC 0';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  STATS_AUTO_RECALC 1';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  STATS_AUTO_RECALC DEFAULT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// STATS_PERSISTENT
$query = 'ALTER TABLE foo
  STATS_PERSISTENT 0';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  STATS_PERSISTENT 1';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  STATS_PERSISTENT DEFAULT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// STATS_SAMPLE_PAGES
$query = 'ALTER TABLE foo
  STATS_SAMPLE_PAGES 17';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// TABLESPACE
$query = "ALTER TABLE foo
  TABLESPACE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// UNION
$query = 'ALTER TABLE foo
  UNION (foo, bar)';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
