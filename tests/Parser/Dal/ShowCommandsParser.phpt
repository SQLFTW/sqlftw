<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());


// SHOW COUNT(*) ERRORS
$query = "SHOW COUNT(*) ERRORS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW COUNT(*) WARNINGS
$query = "SHOW COUNT(*) WARNINGS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW BINLOG EVENTS [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
$query = "SHOW BINLOG EVENTS IN 'foo' FROM 123";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW BINLOG EVENTS IN 'foo' LIMIT 123, 456";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW CHARACTER SET [LIKE 'pattern' | WHERE expr]
$query = "SHOW CHARACTER SET LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW CHARACTER SET WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW COLLATION [LIKE 'pattern' | WHERE expr]
$query = "SHOW COLLATION LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW COLLATION WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW CREATE {DATABASE | SCHEMA} db_name
$query = "SHOW CREATE DATABASE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW CREATE SCHEMA foo";
$result = "SHOW CREATE DATABASE foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));


// SHOW CREATE EVENT event_name
$query = "SHOW CREATE EVENT foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW CREATE FUNCTION func_name
$query = "SHOW CREATE FUNCTION foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW CREATE PROCEDURE proc_name
$query = "SHOW CREATE PROCEDURE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW CREATE TABLE tbl_name
$query = "SHOW CREATE TABLE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW CREATE TRIGGER trigger_name
$query = "SHOW CREATE TRIGGER foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW CREATE USER user
$query = "SHOW CREATE USER foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW CREATE VIEW view_name
$query = "SHOW CREATE VIEW foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW {DATABASES | SCHEMAS} [LIKE 'pattern' | WHERE expr]
$query = "SHOW DATABASES LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW DATABASES WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW SCHEMAS LIKE 'foo'";
$result = "SHOW DATABASES LIKE 'foo'";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW SCHEMAS WHERE bar = baz";
$result = "SHOW DATABASES WHERE bar = baz";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));


// SHOW ENGINE engine_name {STATUS | MUTEX}
$query = "SHOW ENGINE innodb STATUS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW ENGINE innodb MUTEX";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW [STORAGE] ENGINES
$query = "SHOW ENGINES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW STORAGE ENGINES";
$result = "SHOW ENGINES";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));


// SHOW ERRORS [LIMIT [offset,] row_count]
$query = "SHOW ERRORS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW ERRORS LIMIT 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW ERRORS LIMIT 10, 20";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW EVENTS [{FROM | IN} schema_name] [LIKE 'pattern' | WHERE expr]
$query = "SHOW EVENTS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW EVENTS FROM db";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW EVENTS IN db";
$result = "SHOW EVENTS FROM db";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW EVENTS LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW EVENTS WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW EVENTS FROM db LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW EVENTS FROM db WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW FUNCTION CODE func_name
$query = "SHOW FUNCTION CODE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW FUNCTION STATUS [LIKE 'pattern' | WHERE expr]
$query = "SHOW FUNCTION STATUS LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW FUNCTION STATUS WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW GRANTS [FOR user_or_role [USING role [, role] ...]]
$query = "SHOW GRANTS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW GRANTS FOR 'foo'@'bar'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW GRANTS FOR 'foo'@'bar' USING 'bar'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW GRANTS FOR 'foo'@'bar' USING 'bar', 'baz'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW {INDEX | INDEXES | KEYS} {FROM | IN} tbl_name [{FROM | IN} db_name] [WHERE expr]
$query = "SHOW INDEXES FROM foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW KEYS FROM foo";
$result = "SHOW INDEXES FROM foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW INDEX FROM foo";
$result = "SHOW INDEXES FROM foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW INDEXES IN foo";
$result = "SHOW INDEXES FROM foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW INDEXES FROM foo FROM bar";
$result = "SHOW INDEXES FROM bar.foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW INDEXES FROM foo IN bar";
$result = "SHOW INDEXES FROM bar.foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW INDEXES FROM bar.foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW INDEXES FROM foo WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW OPEN TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
$query = "SHOW OPEN TABLES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW OPEN TABLES FROM foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW OPEN TABLES IN foo";
$result = "SHOW OPEN TABLES FROM foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW OPEN TABLES LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW OPEN TABLES WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW OPEN TABLES FROM foo LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW OPEN TABLES FROM foo WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW PLUGINS
$query = "SHOW PLUGINS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW PRIVILEGES
$query = "SHOW PRIVILEGES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW PROCEDURE CODE proc_name
$query = "SHOW PROCEDURE CODE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW PROCEDURE STATUS [LIKE 'pattern' | WHERE expr]
$query = "SHOW PROCEDURE STATUS LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW PROCEDURE STATUS WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW PROFILE [type [, type] ... ] [FOR QUERY n] [LIMIT row_count [OFFSET offset]]
$query = "SHOW PROFILE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW PROFILE ALL";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW PROFILE BLOCK IO, CONTEXT SWITCHES, CPU, IPC, MEMORY, PAGE FAULTS, SOURCE, SWAPS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW PROFILE FOR QUERY 123";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW PROFILE LIMIT 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW PROFILE LIMIT 10 OFFSET 20";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW PROFILES
$query = "SHOW PROFILES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW RELAYLOG EVENTS [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
$query = "SHOW RELAYLOG EVENTS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW RELAYLOG EVENTS IN 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW RELAYLOG EVENTS FROM 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW RELAYLOG EVENTS LIMIT 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW RELAYLOG EVENTS LIMIT 10, 20";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW RELAYLOG EVENTS IN 'foo' FROM 10 LIMIT 10, 20";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW SLAVE HOSTS
$query = "SHOW SLAVE HOSTS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW SLAVE STATUS [FOR CHANNEL channel]
$query = "SHOW SLAVE STATUS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW SLAVE STATUS FOR CHANNEL foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW TABLE STATUS [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
$query = "SHOW TABLE STATUS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TABLE STATUS FROM foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TABLE STATUS IN foo";
$result = "SHOW TABLE STATUS FROM foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TABLE STATUS LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TABLE STATUS WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TABLE STATUS FROM foo LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TABLE STATUS FROM foo WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW TRIGGERS [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
$query = "SHOW TRIGGERS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TRIGGERS FROM foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TRIGGERS IN foo";
$result = "SHOW TRIGGERS FROM foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TRIGGERS LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TRIGGERS WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TRIGGERS FROM foo LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TRIGGERS FROM foo WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW WARNINGS [LIMIT [offset,] row_count]
$query = "SHOW WARNINGS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW WARNINGS LIMIT 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW WARNINGS LIMIT 10, 20";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW MASTER STATUS
$query = "SHOW MASTER STATUS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW {BINARY | MASTER} LOGS
$query = "SHOW BINARY LOGS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW MASTER LOGS";
$result = "SHOW BINARY LOGS";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));


// SHOW [GLOBAL | SESSION] STATUS [LIKE 'pattern' | WHERE expr]
$query = "SHOW STATUS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW GLOBAL STATUS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW SESSION STATUS";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW STATUS LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW STATUS WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW [GLOBAL | SESSION] VARIABLES [LIKE 'pattern' | WHERE expr]
$query = "SHOW VARIABLES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW GLOBAL VARIABLES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW SESSION VARIABLES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW VARIABLES LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW VARIABLES WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW [FULL] COLUMNS {FROM | IN} tbl_name [LIKE 'pattern' | WHERE expr]
$query = "SHOW COLUMNS FROM foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW FULL COLUMNS FROM foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW COLUMNS IN foo";
$result = "SHOW COLUMNS FROM foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW COLUMNS FROM foo LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW COLUMNS FROM foo WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW [FULL] PROCESSLIST
$query = "SHOW PROCESSLIST";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW FULL PROCESSLIST";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// SHOW [FULL] TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
$query = "SHOW TABLES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TABLES FROM foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW FULL TABLES FROM foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TABLES IN foo";
$result = "SHOW TABLES FROM foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TABLES FROM foo LIKE 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "SHOW TABLES FROM foo WHERE bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
