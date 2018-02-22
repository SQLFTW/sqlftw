<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// ALGORITHM
$query = "ALTER TABLE foo
  ALGORITHM INPLACE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER TABLE foo
  ALGORITHM COPY";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER TABLE foo
  ALGORITHM DEFAULT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// LOCK
$query = "ALTER TABLE foo
  LOCK DEFAULT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER TABLE foo
  LOCK NONE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER TABLE foo
  LOCK SHARED";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER TABLE foo
  LOCK EXCLUSIVE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// FORCE
$query = "ALTER TABLE foo
  FORCE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// VALIDATION
$query = "ALTER TABLE foo
  WITH VALIDATION";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER TABLE foo
  WITHOUT VALIDATION";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));