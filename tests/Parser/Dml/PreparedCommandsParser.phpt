<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// {DEALLOCATE | DROP} PREPARE stmt_name
$query = "DEALLOCATE PREPARE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "DROP PREPARE foo";
$result = "DEALLOCATE PREPARE foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));


// EXECUTE stmt_name [USING @var_name [, @var_name] ...]
$query = "EXECUTE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "EXECUTE foo USING @bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "EXECUTE foo USING @bar, @baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// PREPARE stmt_name FROM preparable_stmt
$query = "PREPARE foo FROM 'SELECT 1'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "PREPARE foo FROM @bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
