<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// DO expr [, expr] ...
$query = "DO foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "DO foo(bar)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "DO foo(bar, baz)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "DO foo(), bar()";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
