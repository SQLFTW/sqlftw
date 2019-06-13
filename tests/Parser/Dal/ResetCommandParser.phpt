<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// reset_option [, reset_option] ...
$query = "RESET MASTER, SLAVE, QUERY CACHE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
