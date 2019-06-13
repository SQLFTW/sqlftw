<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// XA {START|BEGIN} xid [JOIN|RESUME]
$query = "XA START 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "XA START 'foo', 'bar'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "XA START 'foo', 'bar', 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "XA BEGIN 'foo'";
$result = "XA START 'foo'";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "XA START 'foo' JOIN";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "XA START 'foo' RESUME";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// XA END xid [SUSPEND [FOR MIGRATE]]
$query = "XA END 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "XA END 'foo' SUSPEND";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "XA END 'foo' SUSPEND FOR MIGRATE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// XA PREPARE xid
$query = "XA PREPARE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// XA COMMIT xid [ONE PHASE]
$query = "XA COMMIT 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "XA COMMIT 'foo' ONE PHASE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// XA ROLLBACK xid
$query = "XA ROLLBACK 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// XA RECOVER [CONVERT XID]
$query = "XA RECOVER";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "XA RECOVER CONVERT XID";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
