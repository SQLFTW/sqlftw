<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// GRANT priv_type [(column_list)] [, priv_type [(column_list)]]... ON [object_type] priv_level TO user_or_role [, user_or_role] ...

// ON
$query = "GRANT ALL ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT ALL ON *.* TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT ALL ON foo TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT ALL ON foo.* TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT ALL ON foo.bar TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT ALL ON TABLE foo TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT ALL ON FUNCTION foo TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT ALL ON PROCEDURE foo TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// privileges
$query = "GRANT ALTER ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT ALTER ROUTINE ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT CREATE ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT CREATE ROUTINE ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT CREATE TABLESPACE ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT CREATE TEMPORARY TABLES ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT CREATE USER ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT CREATE VIEW ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT DELETE ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT DROP ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT EVENT ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT EXECUTE ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT FILE ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT GRANT OPTION ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT INDEX ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT INSERT ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT LOCK TABLES ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT PROCESS ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT REFERENCES ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT RELOAD ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT REPLICATION CLIENT ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT REPLICATION SLAVE ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT SELECT ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT SHOW DATABASES ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT SHOW VIEW ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT SHUTDOWN ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT SUPER ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT TRIGGER ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT UPDATE ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT USAGE ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT SELECT, UPDATE ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT SELECT (col1), UPDATE (col2, col3) ON * TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// WITH GRANT OPTION
$query = "GRANT ALL ON * TO 'admin'@'localhost' WITH GRANT OPTION";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// AS
$query = "GRANT ALL ON * TO 'admin'@'localhost' AS 'developer'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// WITH ROLE
$query = "GRANT ALL ON * TO 'admin'@'localhost' AS 'developer'@'localhost' WITH ROLE DEFAULT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT ALL ON * TO 'admin'@'localhost' AS 'developer'@'localhost' WITH ROLE NONE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT ALL ON * TO 'admin'@'localhost' AS 'developer'@'localhost' WITH ROLE ALL";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT ALL ON * TO 'admin'@'localhost' AS 'developer'@'localhost' WITH ROLE ALL EXCEPT admin, developer";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT ALL ON * TO 'admin'@'localhost' AS 'developer'@'localhost' WITH ROLE admin, developer";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// todo: REQUIRE and WITH resource_option from MySQL 5


// GRANT PROXY ON user TO user [, user] ... [WITH GRANT OPTION]
$query = "GRANT PROXY ON 'admin'@'localhost' TO 'developer'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT PROXY ON 'admin'@'localhost' TO 'developer'@'localhost', 'tester'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT PROXY ON 'admin'@'localhost' TO 'developer'@'localhost' WITH GRANT OPTION";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// GRANT role [, role] ... TO user [, user] ... [WITH ADMIN OPTION]
$query = "GRANT admin TO 'developer'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT admin, tester TO 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT admin TO 'developer'@'localhost', 'tester'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "GRANT admin TO 'developer'@'localhost' WITH ADMIN OPTION";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
