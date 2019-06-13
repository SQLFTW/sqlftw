<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// CREATE FUNCTION SONAME
$query = 'CREATE FUNCTION function_name RETURNS STRING SONAME shared_library_name';
$result = "CREATE FUNCTION function_name RETURNS STRING SONAME 'shared_library_name'";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE AGGREGATE FUNCTION function_name RETURNS REAL SONAME 'shared_library_name'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
