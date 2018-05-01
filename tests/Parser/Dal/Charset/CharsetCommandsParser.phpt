<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// SET CHARSET
$query = "SET CHARACTER SET 'utf8'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'SET CHARSET DEFAULT';
$result = 'SET CHARACTER SET DEFAULT';
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

// SET NAMES
$query = "SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'SET NAMES DEFAULT';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
