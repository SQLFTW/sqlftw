<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// REVOKE priv_type [(column_list)] [, priv_type [(column_list)]] ... ON [object_type] priv_level FROM user [, user] ...

// ON
$query = "REVOKE ALTER ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE ALTER ON *.* FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE ALTER ON foo FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE ALTER ON foo.* FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE ALTER ON foo.bar FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE ALTER ON TABLE foo FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE ALTER ON FUNCTION foo FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE ALTER ON PROCEDURE foo FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// privileges
$query = "REVOKE ALTER ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE ALTER ROUTINE ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE CREATE ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE CREATE ROUTINE ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE CREATE TABLESPACE ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE CREATE TEMPORARY TABLES ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE CREATE USER ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE CREATE VIEW ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE DELETE ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE DROP ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE EVENT ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE EXECUTE ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE FILE ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE GRANT OPTION ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE INDEX ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE INSERT ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE LOCK TABLES ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE PROCESS ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE REFERENCES ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE RELOAD ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE REPLICATION CLIENT ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE REPLICATION SLAVE ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE SELECT ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE SHOW DATABASES ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE SHOW VIEW ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE SHUTDOWN ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE SUPER ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE TRIGGER ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE UPDATE ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE USAGE ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE SELECT, UPDATE ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE SELECT (col1), UPDATE (col2, col3) ON * FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// REVOKE ALL [PRIVILEGES], GRANT OPTION FROM user [, user] ...
$query = "REVOKE ALL, GRANT OPTION FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'admin'@'localhost'";
$result = "REVOKE ALL, GRANT OPTION FROM 'admin'@'localhost'";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE ALL, GRANT OPTION FROM 'admin'@'localhost', 'developer'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// REVOKE PROXY ON user FROM user [, user] ...
$query = "REVOKE PROXY ON 'admin'@'localhost' FROM 'developer'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE PROXY ON 'admin'@'localhost' FROM 'developer'@'localhost', 'tester'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// REVOKE role [, role] ... FROM user [, user] ...
$query = "REVOKE admin FROM 'developer'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE admin, tester FROM 'admin'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REVOKE admin FROM 'developer'@'localhost', 'tester'@'localhost'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
