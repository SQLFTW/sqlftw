<?php

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

$query = "BINLOG 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
