<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// CHANGE MASTER TO
$query = "INSTALL PLUGIN foo SONAME 'library.so'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// CHANGE REPLICATION FILTER
$query = 'UNINSTALL PLUGIN foo';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// PURGE { BINARY | MASTER } LOGS

// RESET MASTER

// RESET SLAVE

// START GROUP_REPLICATION

// START SLAVE

// STOP GROUP_REPLICATION

// STOP SLAVE
