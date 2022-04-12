<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// SHOW COUNT(*) ERRORS
Assert::parse("SHOW COUNT(*) ERRORS");


// SHOW COUNT(*) WARNINGS
Assert::parse("SHOW COUNT(*) WARNINGS");


// SHOW BINLOG EVENTS [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
Assert::parse("SHOW BINLOG EVENTS IN 'log1' FROM 123");
Assert::parse("SHOW BINLOG EVENTS IN 'log1' LIMIT 123, 456");


// SHOW CHARACTER SET [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW CHARACTER SET LIKE 'chs1'");
Assert::parse("SHOW CHARACTER SET WHERE col1 = 1");


// SHOW COLLATION [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW COLLATION LIKE 'col1'");
Assert::parse("SHOW COLLATION WHERE col1 = 1");


// SHOW CREATE {DATABASE | SCHEMA} db_name
Assert::parse("SHOW CREATE DATABASE db1");
Assert::parse("SHOW CREATE SCHEMA db1", "SHOW CREATE DATABASE db1"); // SCHEMA -> DATABASE


// SHOW CREATE EVENT event_name
Assert::parse("SHOW CREATE EVENT evt1");


// SHOW CREATE FUNCTION func_name
Assert::parse("SHOW CREATE FUNCTION func1");


// SHOW CREATE PROCEDURE proc_name
Assert::parse("SHOW CREATE PROCEDURE proc1");


// SHOW CREATE TABLE tbl_name
Assert::parse("SHOW CREATE TABLE tbl1");


// SHOW CREATE TRIGGER trigger_name
Assert::parse("SHOW CREATE TRIGGER trig1");


// SHOW CREATE USER user
Assert::parse("SHOW CREATE USER usr1");


// SHOW CREATE VIEW view_name
Assert::parse("SHOW CREATE VIEW view1");


// SHOW {DATABASES | SCHEMAS} [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW DATABASES LIKE 'db1'");
Assert::parse("SHOW DATABASES WHERE col1 = 1");
Assert::parse("SHOW SCHEMAS LIKE 'db1'", "SHOW DATABASES LIKE 'db1'"); // SCHEMAS -> DATABASES
Assert::parse("SHOW SCHEMAS WHERE col1 = 1", "SHOW DATABASES WHERE col1 = 1"); // SCHEMAS -> DATABASES


// SHOW ENGINE engine_name {STATUS | MUTEX}
Assert::parse("SHOW ENGINE innodb STATUS");
Assert::parse("SHOW ENGINE innodb MUTEX");


// SHOW [STORAGE] ENGINES
Assert::parse("SHOW ENGINES");
Assert::parse("SHOW STORAGE ENGINES", "SHOW ENGINES"); // [STORAGE]


// SHOW ERRORS [LIMIT [offset,] row_count]
Assert::parse("SHOW ERRORS");
Assert::parse("SHOW ERRORS LIMIT 10");
Assert::parse("SHOW ERRORS LIMIT 10, 20");


// SHOW EVENTS [{FROM | IN} schema_name] [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW EVENTS");
Assert::parse("SHOW EVENTS FROM db1");
Assert::parse("SHOW EVENTS IN db1", "SHOW EVENTS FROM db1"); // IN -> FROM
Assert::parse("SHOW EVENTS LIKE 'evt1'");
Assert::parse("SHOW EVENTS WHERE col1 = 1");
Assert::parse("SHOW EVENTS FROM db1 LIKE 'evt1'");
Assert::parse("SHOW EVENTS FROM db1 WHERE col1 = 1");


// SHOW FUNCTION CODE func_name
Assert::parse("SHOW FUNCTION CODE func1");


// SHOW FUNCTION STATUS [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW FUNCTION STATUS LIKE 'func1'");
Assert::parse("SHOW FUNCTION STATUS WHERE col1 = 1");


// SHOW GRANTS [FOR user_or_role [USING role [, role] ...]]
Assert::parse("SHOW GRANTS");
Assert::parse("SHOW GRANTS FOR CURRENT_USER");
Assert::parse("SHOW GRANTS FOR CURRENT_USER()", "SHOW GRANTS FOR CURRENT_USER");
Assert::parse("SHOW GRANTS FOR usr1@host1");
Assert::parse("SHOW GRANTS FOR usr1@host1 USING role1");
Assert::parse("SHOW GRANTS FOR usr1@host1 USING role1, role2");


// SHOW {INDEX | INDEXES | KEYS} {FROM | IN} tbl_name [{FROM | IN} db_name] [WHERE expr]
Assert::parse("SHOW INDEXES FROM tbl1");
Assert::parse("SHOW KEYS FROM tbl1", "SHOW INDEXES FROM tbl1");
Assert::parse("SHOW INDEX FROM tbl1", "SHOW INDEXES FROM tbl1");
Assert::parse("SHOW INDEXES IN tbl1", "SHOW INDEXES FROM tbl1");
Assert::parse("SHOW INDEXES FROM tbl1 FROM db1", "SHOW INDEXES FROM db1.tbl1"); // FROM FROM -> .
Assert::parse("SHOW INDEXES FROM tbl1 IN db1", "SHOW INDEXES FROM db1.tbl1"); // FROM IN -> .
Assert::parse("SHOW INDEXES FROM db1.tbl1");
Assert::parse("SHOW INDEXES FROM tbl1 WHERE col1 = 1");


// SHOW OPEN TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW OPEN TABLES");
Assert::parse("SHOW OPEN TABLES FROM db1");
Assert::parse("SHOW OPEN TABLES IN db1", "SHOW OPEN TABLES FROM db1"); // IN -> FROM
Assert::parse("SHOW OPEN TABLES LIKE 'tbl1'");
Assert::parse("SHOW OPEN TABLES WHERE col1 = 1");
Assert::parse("SHOW OPEN TABLES FROM db1 LIKE 'tbl1'");
Assert::parse("SHOW OPEN TABLES FROM db1 WHERE col1 = 1");


// SHOW PLUGINS
Assert::parse("SHOW PLUGINS");


// SHOW PRIVILEGES
Assert::parse("SHOW PRIVILEGES");


// SHOW PROCEDURE CODE proc_name
Assert::parse("SHOW PROCEDURE CODE proc1");


// SHOW PROCEDURE STATUS [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW PROCEDURE STATUS LIKE 'proc1'");
Assert::parse("SHOW PROCEDURE STATUS WHERE col1 = 1");


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
Assert::parse("SHOW RELAYLOG EVENTS IN 'log1'");
Assert::parse("SHOW RELAYLOG EVENTS FROM 10");
Assert::parse("SHOW RELAYLOG EVENTS LIMIT 10");
Assert::parse("SHOW RELAYLOG EVENTS LIMIT 10, 20");
Assert::parse("SHOW RELAYLOG EVENTS IN 'log1' FROM 10 LIMIT 10, 20");


// SHOW SLAVE HOSTS
Assert::parse("SHOW SLAVE HOSTS");


// SHOW SLAVE STATUS [FOR CHANNEL channel]
Assert::parse("SHOW SLAVE STATUS");
Assert::parse("SHOW SLAVE STATUS FOR CHANNEL chan1");


// SHOW TABLE STATUS [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW TABLE STATUS");
Assert::parse("SHOW TABLE STATUS FROM db1");
Assert::parse("SHOW TABLE STATUS IN db1", "SHOW TABLE STATUS FROM db1"); // IN -> FROM
Assert::parse("SHOW TABLE STATUS LIKE 'tbl1'");
Assert::parse("SHOW TABLE STATUS WHERE col1 = 1");
Assert::parse("SHOW TABLE STATUS FROM db1 LIKE 'tbl1'");
Assert::parse("SHOW TABLE STATUS FROM db1 WHERE col1 = 1");


// SHOW TRIGGERS [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW TRIGGERS");
Assert::parse("SHOW TRIGGERS FROM db1");
Assert::parse("SHOW TRIGGERS IN db1", "SHOW TRIGGERS FROM db1"); // IN -> FROM
Assert::parse("SHOW TRIGGERS LIKE 'tbl1'");
Assert::parse("SHOW TRIGGERS WHERE col1 = 1");
Assert::parse("SHOW TRIGGERS FROM db1 LIKE 'tbl1'");
Assert::parse("SHOW TRIGGERS FROM db1 WHERE col1 = 1");


// SHOW WARNINGS [LIMIT [offset,] row_count]
Assert::parse("SHOW WARNINGS");
Assert::parse("SHOW WARNINGS LIMIT 10");
Assert::parse("SHOW WARNINGS LIMIT 10, 20");


// SHOW MASTER STATUS
Assert::parse("SHOW MASTER STATUS");


// SHOW {BINARY | MASTER} LOGS
Assert::parse("SHOW BINARY LOGS");
Assert::parse("SHOW MASTER LOGS", "SHOW BINARY LOGS"); // MASTER LOGS -> BINARY LOGS


// SHOW [GLOBAL | SESSION] STATUS [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW STATUS");
Assert::parse("SHOW GLOBAL STATUS");
Assert::parse("SHOW SESSION STATUS");
Assert::parse("SHOW STATUS LIKE 'stat1'");
Assert::parse("SHOW STATUS WHERE col1 = 1");


// SHOW [GLOBAL | SESSION] VARIABLES [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW VARIABLES");
Assert::parse("SHOW GLOBAL VARIABLES");
Assert::parse("SHOW SESSION VARIABLES");
Assert::parse("SHOW VARIABLES LIKE 'var1'");
Assert::parse("SHOW VARIABLES WHERE col1 = 1");


// SHOW [FULL] COLUMNS {FROM | IN} tbl_name [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW COLUMNS FROM tbl1");
Assert::parse("SHOW FULL COLUMNS FROM tbl1");
Assert::parse("SHOW COLUMNS IN tbl1", "SHOW COLUMNS FROM tbl1"); // IN -> FROM
Assert::parse("SHOW COLUMNS FROM tbl1 LIKE 'col1'");
Assert::parse("SHOW COLUMNS FROM tbl1 WHERE col1 = 1");


// SHOW [FULL] PROCESSLIST
Assert::parse("SHOW PROCESSLIST");
Assert::parse("SHOW FULL PROCESSLIST");


// SHOW [FULL] TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
Assert::parse("SHOW TABLES");
Assert::parse("SHOW TABLES FROM db1");
Assert::parse("SHOW FULL TABLES FROM db1");
Assert::parse("SHOW TABLES IN db1", "SHOW TABLES FROM db1"); // IN -> FROM
Assert::parse("SHOW TABLES FROM db1 LIKE 'tbl1'");
Assert::parse("SHOW TABLES FROM db1 WHERE col1 = 1");
