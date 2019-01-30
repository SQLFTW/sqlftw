<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// CHANGE MASTER TO
///$query = "INSTALL PLUGIN foo SONAME 'library.so'";
///Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// CHANGE REPLICATION FILTER
///$query = 'UNINSTALL PLUGIN foo';
///Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// RESET MASTER
$query = 'RESET MASTER';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'RESET MASTER TO 12345';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// RESET SLAVE
$query = 'RESET SLAVE';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = 'RESET SLAVE ALL';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "RESET SLAVE FOR CHANNEL 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "RESET SLAVE ALL FOR CHANNEL 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// START GROUP_REPLICATION
$query = "START GROUP_REPLICATION";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// START SLAVE
$query = "START SLAVE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START SLAVE IO_THREAD";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START SLAVE SQL_THREAD";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START SLAVE IO_THREAD, SQL_THREAD";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START SLAVE UNTIL SQL_BEFORE_GTIDS = 5ade17eb-fb52-49e5-80c9-6b952de466b7:10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START SLAVE UNTIL SQL_AFTER_GTIDS = 5ade17eb-fb52-49e5-80c9-6b952de466b7:10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START SLAVE UNTIL SQL_AFTER_GTIDS = 5ade17eb-fb52-49e5-80c9-6b952de466b7:10-20:30-40:50-60";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START SLAVE UNTIL SQL_AFTER_GTIDS = 5ade17eb-fb52-49e5-80c9-6b952de466b7:10-20:30-40:50-60, 82c4b49c-b591-4249-8600-d2ba6a528791:70-80";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START SLAVE USER='foo' PASSWORD='bar' DEFAULT_AUTH='baz' PLUGIN_DIR='dir'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START SLAVE FOR CHANNEL 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// STOP GROUP_REPLICATION
$query = 'STOP GROUP_REPLICATION';
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// STOP SLAVE
$query = "STOP SLAVE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "STOP SLAVE IO_THREAD";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "STOP SLAVE SQL_THREAD";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "STOP SLAVE IO_THREAD, SQL_THREAD";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "STOP SLAVE FOR CHANNEL 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
