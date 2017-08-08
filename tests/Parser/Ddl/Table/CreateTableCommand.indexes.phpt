<?php

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

$query = "CREATE TABLE test (
  id BIGINT,
  foo CHAR(10),
  bar CHAR(20),
  PRIMARY KEY (id),
  UNIQUE KEY key2 (foo(5), bar(10)),
  KEY key3 (bar) USING HASH
)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

