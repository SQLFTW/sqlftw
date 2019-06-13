<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// RESET PERSIST [[IF EXISTS] system_var_name]
$query = "RESET PERSIST";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "RESET PERSIST foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "RESET PERSIST IF EXISTS foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
