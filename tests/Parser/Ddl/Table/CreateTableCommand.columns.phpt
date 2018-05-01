<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

$query = "CREATE TABLE test (
  col_0 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  col_1 INT NULL DEFAULT NULL,
  col_2 MEDIUMINT NULL,
  col_3 SMALLINT DEFAULT NULL,
  col_4 TINYINT(3) DEFAULT 10,
  col_5 BIT,
  col_6 FLOAT,
  col_7 DOUBLE(10, 2),
  col_8 DECIMAL(10, 2),
  col_9 YEAR,
  col_10 DATE,
  col_11 DATETIME,
  col_12 TIME,
  col_13 TIMESTAMP,
  col_14 CHAR(1) CHARACTER SET 'ascii',
  col_15 VARCHAR(10) COLLATE 'ascii_general_ci',
  col_16 TINYTEXT CHARACTER SET 'ascii' COLLATE 'ascii_general_ci' DEFAULT 'foo',
  col_17 TEXT,
  col_18 MEDIUMTEXT,
  col_19 LONGTEXT,
  col_20 BINARY(1),
  col_21 VARBINARY(10),
  col_22 TINYBLOB,
  col_23 BLOB,
  col_24 MEDIUMBLOB,
  col_25 LONGBLOB,
  col_28 JSON,
  col_26 ENUM('a', 'b'),
  col_27 SET('c', 'd')
)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
