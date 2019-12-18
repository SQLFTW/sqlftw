<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// REVOKE priv_type [(column_list)] [, priv_type [(column_list)]] ... ON [object_type] priv_level FROM user [, user] ...
Assert::parse("REVOKE ALTER ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE ALTER ON *.* FROM 'admin'@'localhost'");
Assert::parse("REVOKE ALTER ON foo FROM 'admin'@'localhost'");
Assert::parse("REVOKE ALTER ON foo.* FROM 'admin'@'localhost'");
Assert::parse("REVOKE ALTER ON foo.bar FROM 'admin'@'localhost'");
Assert::parse("REVOKE ALTER ON TABLE foo FROM 'admin'@'localhost'");
Assert::parse("REVOKE ALTER ON FUNCTION foo FROM 'admin'@'localhost'");
Assert::parse("REVOKE ALTER ON PROCEDURE foo FROM 'admin'@'localhost'");

// privileges
Assert::parse("REVOKE ALTER ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE ALTER ROUTINE ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE CREATE ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE CREATE ROUTINE ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE CREATE TABLESPACE ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE CREATE TEMPORARY TABLES ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE CREATE USER ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE CREATE VIEW ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE DELETE ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE DROP ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE EVENT ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE EXECUTE ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE FILE ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE GRANT OPTION ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE INDEX ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE INSERT ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE LOCK TABLES ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE PROCESS ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE REFERENCES ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE RELOAD ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE REPLICATION CLIENT ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE REPLICATION SLAVE ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE SELECT ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE SHOW DATABASES ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE SHOW VIEW ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE SHUTDOWN ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE SUPER ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE TRIGGER ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE UPDATE ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE USAGE ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE SELECT, UPDATE ON * FROM 'admin'@'localhost'");
Assert::parse("REVOKE SELECT (col1), UPDATE (col2, col3) ON * FROM 'admin'@'localhost'");


// REVOKE ALL [PRIVILEGES], GRANT OPTION FROM user [, user] ...
Assert::parse("REVOKE ALL, GRANT OPTION FROM 'admin'@'localhost'");
Assert::parse("REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'admin'@'localhost'", "REVOKE ALL, GRANT OPTION FROM 'admin'@'localhost'");
Assert::parse("REVOKE ALL, GRANT OPTION FROM 'admin'@'localhost', 'developer'@'localhost'");


// REVOKE PROXY ON user FROM user [, user] ...
Assert::parse("REVOKE PROXY ON 'admin'@'localhost' FROM 'developer'@'localhost'");
Assert::parse("REVOKE PROXY ON 'admin'@'localhost' FROM 'developer'@'localhost', 'tester'@'localhost'");


// REVOKE role [, role] ... FROM user [, user] ...
Assert::parse("REVOKE admin FROM 'developer'@'localhost'");
Assert::parse("REVOKE admin, tester FROM 'admin'@'localhost'");
Assert::parse("REVOKE admin FROM 'developer'@'localhost', 'tester'@'localhost'");
