<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// COMMIT [WORK] [AND [NO] CHAIN] [[NO] RELEASE]
$query = "COMMIT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "COMMIT WORK";
$result = "COMMIT";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "COMMIT AND CHAIN";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "COMMIT AND NO CHAIN";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "COMMIT RELEASE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "COMMIT NO RELEASE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// LOCK TABLES tbl_name [[AS] alias] lock_type [, tbl_name [[AS] alias] lock_type] ...
$query = "LOCK TABLES foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOCK TABLES foo AS foo1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOCK TABLES foo, bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOCK TABLES foo AS foo1, bar AS bar1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOCK TABLES foo AS foo1 READ, bar AS bar1 READ LOCAL";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOCK TABLES foo AS foo1 WRITE, bar AS bar1 LOW_PRIORITY WRITE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// RELEASE SAVEPOINT identifier
$query = "RELEASE SAVEPOINT foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// ROLLBACK [WORK] [AND [NO] CHAIN] [[NO] RELEASE]
$query = "ROLLBACK";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ROLLBACK WORK";
$result = "ROLLBACK";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "ROLLBACK AND CHAIN";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ROLLBACK AND NO CHAIN";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ROLLBACK RELEASE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ROLLBACK NO RELEASE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// ROLLBACK [WORK] TO [SAVEPOINT] identifier
$query = "ROLLBACK TO SAVEPOINT foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ROLLBACK WORK TO SAVEPOINT foo";
$result = "ROLLBACK TO SAVEPOINT foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "ROLLBACK TO foo";
$result = "ROLLBACK TO SAVEPOINT foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));


// SAVEPOINT identifier
$query = "SAVEPOINT foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SET [GLOBAL | SESSION] TRANSACTION transaction_characteristic [, transaction_characteristic] ...
$query = "SET GLOBAL TRANSACTION READ ONLY";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET SESSION TRANSACTION READ WRITE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET TRANSACTION ISOLATION LEVEL REPEATABLE READ";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET TRANSACTION ISOLATION LEVEL READ COMMITTED";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE, READ WRITE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// START TRANSACTION [transaction_characteristic [, transaction_characteristic] ...]
// BEGIN [WORK]
$query = "START TRANSACTION";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START TRANSACTION WITH CONSISTENT SNAPSHOT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START TRANSACTION READ ONLY";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START TRANSACTION READ WRITE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "START TRANSACTION WITH CONSISTENT SNAPSHOT, READ WRITE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "BEGIN";
$result = "START TRANSACTION";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "BEGIN WORK";
$result = "START TRANSACTION";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));


// UNLOCK TABLES
$query = "UNLOCK TABLES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
