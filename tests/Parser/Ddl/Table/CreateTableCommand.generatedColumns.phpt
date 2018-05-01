<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

$query = 'CREATE TABLE test (
  col_0 DATETIME,
  col_1 INT AS (YEAR(col_0)),
  col_2 INT AS (YEAR(col_0)) VIRTUAL,
  col_3 INT AS (YEAR(col_0)) STORED,
  col_4 INT GENERATED ALWAYS AS (YEAR(col_0)),
  col_5 INT GENERATED ALWAYS AS (YEAR(col_0)) VIRTUAL,
  col_6 INT GENERATED ALWAYS AS (YEAR(col_0)) STORED
)';
$result = 'CREATE TABLE test (
  col_0 DATETIME,
  col_1 INT GENERATED ALWAYS AS YEAR(col_0),
  col_2 INT GENERATED ALWAYS AS YEAR(col_0) VIRTUAL,
  col_3 INT GENERATED ALWAYS AS YEAR(col_0) STORED,
  col_4 INT GENERATED ALWAYS AS YEAR(col_0),
  col_5 INT GENERATED ALWAYS AS YEAR(col_0) VIRTUAL,
  col_6 INT GENERATED ALWAYS AS YEAR(col_0) STORED
)';
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));
