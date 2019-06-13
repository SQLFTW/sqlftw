<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// CREATE ROLE [IF NOT EXISTS] role [, role ] ...
$query = "CREATE ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE ROLE admin, developer";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE ROLE IF NOT EXISTS admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// DROP ROLE [IF EXISTS] role [, role ] ...
$query = "DROP ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "DROP ROLE admin, developer";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "DROP ROLE IF EXISTS admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// DROP USER [IF EXISTS] user [, user] ...
$query = "DROP USER 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "DROP USER 'admin'@'localhost', 'developer'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "DROP USER IF EXISTS 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// RENAME USER old_user TO new_user [, old_user TO new_user] ...
$query = "RENAME USER 'admin'@'localhost' TO 'almighty'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "RENAME USER 'admin'@'localhost' TO 'almighty'@'localhost', 'developer'@'localhost' TO 'lama'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SET DEFAULT ROLE {NONE | ALL | role [, role ] ...} TO user [, user ] ...
$query = "SET DEFAULT ROLE NONE TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET DEFAULT ROLE ALL TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET DEFAULT ROLE admin TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET DEFAULT ROLE admin, developer TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET DEFAULT ROLE admin TO 'admin'@'localhost', 'developer'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SET PASSWORD [FOR user] = {PASSWORD('auth_string') | 'auth_string'}
$query = "SET PASSWORD = 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET PASSWORD FOR 'admin'@'localhost' = 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET PASSWORD = PASSWORD('foo')";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SET ROLE {DEFAULT | NONE | ALL | ALL EXCEPT role [, role ] ... | role [, role ] ... }
$query = "SET ROLE DEFAULT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET ROLE NONE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET ROLE ALL";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET ROLE ALL EXCEPT admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET ROLE ALL EXCEPT admin, developer";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SET ROLE admin, developer";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
