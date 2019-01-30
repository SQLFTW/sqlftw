<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// PURGE { BINARY | MASTER } LOGS
$query = "PURGE BINARY LOGS TO 'foo.log'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$queryAlternative = "PURGE MASTER LOGS TO 'foo.log'";
Assert::same($query, $parser->parseCommand($queryAlternative)->serialize($formatter));

$query = "PURGE BINARY LOGS BEFORE '2001-01-01 01:01:01'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$queryAlternative = "PURGE MASTER LOGS BEFORE '2001-01-01 01:01:01'";
Assert::same($query, $parser->parseCommand($queryAlternative)->serialize($formatter));

// RESET MASTER
$query = 'RESET MASTER';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'RESET MASTER TO 123';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// START GROUP_REPLICATION
$query = 'START GROUP_REPLICATION';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// STOP GROUP_REPLICATION
$query = 'STOP GROUP_REPLICATION';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
