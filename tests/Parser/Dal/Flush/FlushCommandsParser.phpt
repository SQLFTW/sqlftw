<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// FLUSH
$query = "FLUSH NO_WRITE_TO_BINLOG DES_KEY_FILE";
$result = "FLUSH LOCAL DES_KEY_FILE";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH LOCAL HOSTS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH BINARY LOGS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH ENGINE LOGS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH ERROR LOGS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH GENERAL LOGS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH RELAY LOGS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH SLOW LOGS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH RELAY LOGS FOR CHANNEL foo";
$result = "FLUSH RELAY LOGS FOR CHANNEL 'foo'";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH OPTIMIZER_COSTS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH PRIVILEGES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH QUERY CACHE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH STATUS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH USER_RESOURCES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "FLUSH PRIVILEGES, STATUS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
