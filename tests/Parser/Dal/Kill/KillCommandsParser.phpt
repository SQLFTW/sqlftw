<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// KILL
$query = "KILL 17";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "KILL CONNECTION 17";
$result = "KILL 17";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "KILL QUERY 17";
$result = "KILL 17";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));
