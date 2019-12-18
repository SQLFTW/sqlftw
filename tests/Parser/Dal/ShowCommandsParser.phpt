<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// SHOW COUNT(*) ERRORS
Assert::parse("SHOW COUNT(*) ERRORS");


// SHOW COUNT(*) WARNINGS
Assert::parse("SHOW COUNT(*) WARNINGS");


// SHOW BINLOG EVENTS [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
Assert::parse("SHOW BINLOG EVENTS IN 'foo' FROM 123");
Assert::parse("SHOW BINLOG EVENTS IN 'foo' LIMIT 123, 456");


// SHOW CHARACTER SET [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW CHARACTER SET LIKE 'foo'");
Assert::parse("SHOW CHARACTER SET WHERE bar = baz");


// SHOW COLLATION [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW COLLATION LIKE 'foo'");
Assert::parse("SHOW COLLATION WHERE bar = baz");


// SHOW CREATE {DATABASE | SCHEMA} db_name
Assert::parse("SHOW CREATE DATABASE foo");
Assert::parse("SHOW CREATE SCHEMA foo", "SHOW CREATE DATABASE foo");


// SHOW CREATE EVENT event_name
Assert::parse("SHOW CREATE EVENT foo");


// SHOW CREATE FUNCTION func_name
Assert::parse("SHOW CREATE FUNCTION foo");


// SHOW CREATE PROCEDURE proc_name
Assert::parse("SHOW CREATE PROCEDURE foo");


// SHOW CREATE TABLE tbl_name
Assert::parse("SHOW CREATE TABLE foo");


// SHOW CREATE TRIGGER trigger_name
Assert::parse("SHOW CREATE TRIGGER foo");


// SHOW CREATE USER user
Assert::parse("SHOW CREATE USER foo");


// SHOW CREATE VIEW view_name
Assert::parse("SHOW CREATE VIEW foo");


// SHOW {DATABASES | SCHEMAS} [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW DATABASES LIKE 'foo'");
Assert::parse("SHOW DATABASES WHERE bar = baz");
Assert::parse("SHOW SCHEMAS LIKE 'foo'", "SHOW DATABASES LIKE 'foo'");
Assert::parse("SHOW SCHEMAS WHERE bar = baz", "SHOW DATABASES WHERE bar = baz");


// SHOW ENGINE engine_name {STATUS | MUTEX}
Assert::parse("SHOW ENGINE innodb STATUS");
Assert::parse("SHOW ENGINE innodb MUTEX");


// SHOW [STORAGE] ENGINES
Assert::parse("SHOW ENGINES");
Assert::parse("SHOW STORAGE ENGINES", "SHOW ENGINES");


// SHOW ERRORS [LIMIT [offset,] row_count]
Assert::parse("SHOW ERRORS");
Assert::parse("SHOW ERRORS LIMIT 10");
Assert::parse("SHOW ERRORS LIMIT 10, 20");


// SHOW EVENTS [{FROM | IN} schema_name] [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW EVENTS");
Assert::parse("SHOW EVENTS FROM db1");
Assert::parse("SHOW EVENTS IN db1", "SHOW EVENTS FROM db1");
Assert::parse("SHOW EVENTS LIKE 'foo'");
Assert::parse("SHOW EVENTS WHERE bar = baz");
Assert::parse("SHOW EVENTS FROM db LIKE 'foo'");
Assert::parse("SHOW EVENTS FROM db WHERE bar = baz");


// SHOW FUNCTION CODE func_name
Assert::parse("SHOW FUNCTION CODE foo");


// SHOW FUNCTION STATUS [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW FUNCTION STATUS LIKE 'foo'");
Assert::parse("SHOW FUNCTION STATUS WHERE bar = baz");


// SHOW GRANTS [FOR user_or_role [USING role [, role] ...]]
Assert::parse("SHOW GRANTS");
Assert::parse("SHOW GRANTS FOR 'foo'@'bar'");
Assert::parse("SHOW GRANTS FOR 'foo'@'bar' USING 'bar'");
Assert::parse("SHOW GRANTS FOR 'foo'@'bar' USING 'bar', 'baz'");


// SHOW {INDEX | INDEXES | KEYS} {FROM | IN} tbl_name [{FROM | IN} db_name] [WHERE expr]
Assert::parse("SHOW INDEXES FROM foo");
Assert::parse("SHOW KEYS FROM foo", "SHOW INDEXES FROM foo");
Assert::parse("SHOW INDEX FROM foo", "SHOW INDEXES FROM foo");
Assert::parse("SHOW INDEXES IN foo", "SHOW INDEXES FROM foo");
Assert::parse("SHOW INDEXES FROM foo FROM bar", "SHOW INDEXES FROM bar.foo");
Assert::parse("SHOW INDEXES FROM foo IN bar", "SHOW INDEXES FROM bar.foo");
Assert::parse("SHOW INDEXES FROM bar.foo");
Assert::parse("SHOW INDEXES FROM foo WHERE bar = baz");


// SHOW OPEN TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW OPEN TABLES");
Assert::parse("SHOW OPEN TABLES FROM foo");
Assert::parse("SHOW OPEN TABLES IN foo", "SHOW OPEN TABLES FROM foo");
Assert::parse("SHOW OPEN TABLES LIKE 'foo'");
Assert::parse("SHOW OPEN TABLES WHERE bar = baz");
Assert::parse("SHOW OPEN TABLES FROM foo LIKE 'foo'");
Assert::parse("SHOW OPEN TABLES FROM foo WHERE bar = baz");


// SHOW PLUGINS
Assert::parse("SHOW PLUGINS");


// SHOW PRIVILEGES
Assert::parse("SHOW PRIVILEGES");


// SHOW PROCEDURE CODE proc_name
Assert::parse("SHOW PROCEDURE CODE foo");


// SHOW PROCEDURE STATUS [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW PROCEDURE STATUS LIKE 'foo'");
Assert::parse("SHOW PROCEDURE STATUS WHERE bar = baz");


// SHOW PROFILE [type [, type] ... ] [FOR QUERY n] [LIMIT row_count [OFFSET offset]]
Assert::parse("SHOW PROFILE");
Assert::parse("SHOW PROFILE ALL");
Assert::parse("SHOW PROFILE BLOCK IO, CONTEXT SWITCHES, CPU, IPC, MEMORY, PAGE FAULTS, SOURCE, SWAPS");
Assert::parse("SHOW PROFILE FOR QUERY 123");
Assert::parse("SHOW PROFILE LIMIT 10");
Assert::parse("SHOW PROFILE LIMIT 10 OFFSET 20");


// SHOW PROFILES
Assert::parse("SHOW PROFILES");


// SHOW RELAYLOG EVENTS [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
Assert::parse("SHOW RELAYLOG EVENTS");
Assert::parse("SHOW RELAYLOG EVENTS IN 'foo'");
Assert::parse("SHOW RELAYLOG EVENTS FROM 10");
Assert::parse("SHOW RELAYLOG EVENTS LIMIT 10");
Assert::parse("SHOW RELAYLOG EVENTS LIMIT 10, 20");
Assert::parse("SHOW RELAYLOG EVENTS IN 'foo' FROM 10 LIMIT 10, 20");


// SHOW SLAVE HOSTS
Assert::parse("SHOW SLAVE HOSTS");


// SHOW SLAVE STATUS [FOR CHANNEL channel]
Assert::parse("SHOW SLAVE STATUS");
Assert::parse("SHOW SLAVE STATUS FOR CHANNEL foo");


// SHOW TABLE STATUS [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW TABLE STATUS");
Assert::parse("SHOW TABLE STATUS FROM foo");
Assert::parse("SHOW TABLE STATUS IN foo", "SHOW TABLE STATUS FROM foo");
Assert::parse("SHOW TABLE STATUS LIKE 'foo'");
Assert::parse("SHOW TABLE STATUS WHERE bar = baz");
Assert::parse("SHOW TABLE STATUS FROM foo LIKE 'foo'");
Assert::parse("SHOW TABLE STATUS FROM foo WHERE bar = baz");


// SHOW TRIGGERS [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW TRIGGERS");
Assert::parse("SHOW TRIGGERS FROM foo");
Assert::parse("SHOW TRIGGERS IN foo", "SHOW TRIGGERS FROM foo");
Assert::parse("SHOW TRIGGERS LIKE 'foo'");
Assert::parse("SHOW TRIGGERS WHERE bar = baz");
Assert::parse("SHOW TRIGGERS FROM foo LIKE 'foo'");
Assert::parse("SHOW TRIGGERS FROM foo WHERE bar = baz");


// SHOW WARNINGS [LIMIT [offset,] row_count]
Assert::parse("SHOW WARNINGS");
Assert::parse("SHOW WARNINGS LIMIT 10");
Assert::parse("SHOW WARNINGS LIMIT 10, 20");


// SHOW MASTER STATUS
Assert::parse("SHOW MASTER STATUS");


// SHOW {BINARY | MASTER} LOGS
Assert::parse("SHOW BINARY LOGS");
Assert::parse("SHOW MASTER LOGS", "SHOW BINARY LOGS");


// SHOW [GLOBAL | SESSION] STATUS [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW STATUS");
Assert::parse("SHOW GLOBAL STATUS");
Assert::parse("SHOW SESSION STATUS");
Assert::parse("SHOW STATUS LIKE 'foo'");
Assert::parse("SHOW STATUS WHERE bar = baz");


// SHOW [GLOBAL | SESSION] VARIABLES [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW VARIABLES");
Assert::parse("SHOW GLOBAL VARIABLES");
Assert::parse("SHOW SESSION VARIABLES");
Assert::parse("SHOW VARIABLES LIKE 'foo'");
Assert::parse("SHOW VARIABLES WHERE bar = baz");


// SHOW [FULL] COLUMNS {FROM | IN} tbl_name [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW COLUMNS FROM foo");
Assert::parse("SHOW FULL COLUMNS FROM foo");
Assert::parse("SHOW COLUMNS IN foo", "SHOW COLUMNS FROM foo");
Assert::parse("SHOW COLUMNS FROM foo LIKE 'foo'");
Assert::parse("SHOW COLUMNS FROM foo WHERE bar = baz");


// SHOW [FULL] PROCESSLIST
Assert::parse("SHOW PROCESSLIST");
Assert::parse("SHOW FULL PROCESSLIST");


// SHOW [FULL] TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW TABLES");
Assert::parse("SHOW TABLES FROM foo");
Assert::parse("SHOW FULL TABLES FROM foo");
Assert::parse("SHOW TABLES IN foo", "SHOW TABLES FROM foo");
Assert::parse("SHOW TABLES FROM foo LIKE 'foo'");
Assert::parse("SHOW TABLES FROM foo WHERE bar = baz");
