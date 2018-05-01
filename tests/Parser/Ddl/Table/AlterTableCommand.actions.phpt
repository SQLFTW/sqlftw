<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// ADD COLUMN
$query = 'ALTER TABLE foo
  ADD COLUMN foo INT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ADD COLUMN {FIRST|AFTER}
$query = 'ALTER TABLE foo
  ADD COLUMN foo INT FIRST';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ADD COLUMN foo INT AFTER bar';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ADD INDEX|KEY
$query = 'ALTER TABLE foo
  ADD INDEX foo (bar)';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ADD KEY foo (bar)';
$result = $query = 'ALTER TABLE foo
  ADD INDEX foo (bar)';
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

// ADD [CONSTRAINT] PRIMARY KEY
$query = 'ALTER TABLE foo
  ADD PRIMARY KEY (bar)';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ADD CONSTRAINT foo PRIMARY KEY (bar)';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ADD [CONSTRAINT] UNIQUE {INDEX|KEY}
$query = 'ALTER TABLE foo
  ADD UNIQUE KEY (bar)';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ADD UNIQUE INDEX (bar)';
$result = 'ALTER TABLE foo
  ADD UNIQUE KEY (bar)';
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ADD CONSTRAINT foo UNIQUE KEY (bar)';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ADD FULLTEXT [INDEX|KEY]
$query = 'ALTER TABLE foo
  ADD FULLTEXT INDEX (bar)';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ADD FULLTEXT KEY (bar)';
$result = 'ALTER TABLE foo
  ADD FULLTEXT INDEX (bar)';
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

// ADD SPATIAL [INDEX|KEY]
$query = 'ALTER TABLE foo
  ADD SPATIAL INDEX (bar)';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ADD SPATIAL KEY (bar)';
$result = 'ALTER TABLE foo
  ADD SPATIAL INDEX (bar)';
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

// ADD [CONSTRAINT] FOREIGN KEY
$query = 'ALTER TABLE foo
  ADD FOREIGN KEY (bar) REFERENCES table2 (bar)';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ADD CONSTRAINT fk1 FOREIGN KEY (bar) REFERENCES table2 (bar)';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ALTER [COLUMN]
$query = 'ALTER TABLE foo
  ALTER COLUMN foo SET DEFAULT 1';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  ALTER foo DROP DEFAULT';
$result = 'ALTER TABLE foo
  ALTER COLUMN foo DROP DEFAULT';
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

// CHANGE [COLUMN]
$query = 'ALTER TABLE foo
  CHANGE COLUMN foo bar INT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  CHANGE foo bar INT';
$result = 'ALTER TABLE foo
  CHANGE COLUMN foo bar INT';
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

// MODIFY [COLUMN]
$query = 'ALTER TABLE foo
  MODIFY COLUMN foo INT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  MODIFY COLUMN foo INT FIRST';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  MODIFY COLUMN foo INT AFTER bar';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  MODIFY foo INT';
$result = 'ALTER TABLE foo
  MODIFY COLUMN foo INT';
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

// DROP [COLUMN]
$query = 'ALTER TABLE foo
  DROP COLUMN foo';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  DROP foo';
$query = 'ALTER TABLE foo
  DROP COLUMN foo';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// DROP PRIMARY KEY
$query = 'ALTER TABLE foo
  DROP PRIMARY KEY';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// DROP {INDEX|KEY}
$query = 'ALTER TABLE foo
  DROP INDEX foo';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'ALTER TABLE foo
  DROP KEY foo';
$result = 'ALTER TABLE foo
  DROP INDEX foo';
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

// DROP FOREIGN KEY
$query = 'ALTER TABLE foo
  DROP FOREIGN KEY foo';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ALTER INDEX (MySQL 8.0+)
/*
$query = "ALTER TABLE foo
  ALTER INDEX foo VISIBLE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER TABLE foo
  ALTER INDEX foo INVISIBLE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
*/

// DISABLE KEYS
$query = 'ALTER TABLE foo
  DISABLE KEYS';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ENABLE KEYS
$query = 'ALTER TABLE foo
  ENABLE KEYS';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// RENAME TO
$query = 'ALTER TABLE foo
  RENAME TO bar';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// RENAME {INDEX|KEY}
$query = 'ALTER TABLE foo
  RENAME INDEX foo TO bar';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// ORDER BY
$query = 'ALTER TABLE foo
  ORDER BY foo, bar';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// CONVERT TO CHARACTER SET
$query = "ALTER TABLE foo
  CONVERT TO CHARACTER SET 'ascii'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER TABLE foo
  CONVERT TO CHARACTER SET 'ascii' COLLATE 'ascii_general_ci'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// DISCARD TABLESPACE
$query = 'ALTER TABLE foo
  DISCARD TABLESPACE';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// IMPORT TABLESPACE
$query = 'ALTER TABLE foo
  IMPORT TABLESPACE';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
