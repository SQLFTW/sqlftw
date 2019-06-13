<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// SET ...
$query = "SET foo = 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET foo = 1, bar = 2";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET @foo = 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET @@basedir = 1";
$result = "SET SESSION basedir = 1";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SET @@SESSION.basedir = 1";
$result = "SET SESSION basedir = 1";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SET @@GLOBAL.basedir = 1";
$result = "SET GLOBAL basedir = 1";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SET @@PERSIST.basedir = 1";
$result = "SET PERSIST basedir = 1";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SET @@PERSIST_ONLY.basedir = 1";
$result = "SET PERSIST_ONLY basedir = 1";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SET SESSION basedir = 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET GLOBAL basedir = 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET PERSIST basedir = 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET PERSIST_ONLY basedir = 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
