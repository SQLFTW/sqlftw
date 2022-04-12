<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// REVOKE priv_type [(column_list)] [, priv_type [(column_list)]] ... ON [object_type] priv_level FROM user [, user] ...
Assert::parse("REVOKE ALTER ON * FROM usr1@host1");
Assert::parse("REVOKE ALTER ON *.* FROM usr1@host1");
Assert::parse("REVOKE ALTER ON db1 FROM usr1@host1");
Assert::parse("REVOKE ALTER ON db1.* FROM usr1@host1");
Assert::parse("REVOKE ALTER ON db1.tbl1 FROM usr1@host1");
Assert::parse("REVOKE ALTER ON TABLE tbl1 FROM usr1@host1");
Assert::parse("REVOKE ALTER ON FUNCTION func1 FROM usr1@host1");
Assert::parse("REVOKE ALTER ON PROCEDURE proc1 FROM usr1@host1");

// privileges
Assert::parse("REVOKE ALTER ON * FROM usr1@host1");
Assert::parse("REVOKE ALTER ROUTINE ON * FROM usr1@host1");
Assert::parse("REVOKE CREATE ON * FROM usr1@host1");
Assert::parse("REVOKE CREATE ROUTINE ON * FROM usr1@host1");
Assert::parse("REVOKE CREATE TABLESPACE ON * FROM usr1@host1");
Assert::parse("REVOKE CREATE TEMPORARY TABLES ON * FROM usr1@host1");
Assert::parse("REVOKE CREATE USER ON * FROM usr1@host1");
Assert::parse("REVOKE CREATE VIEW ON * FROM usr1@host1");
Assert::parse("REVOKE DELETE ON * FROM usr1@host1");
Assert::parse("REVOKE DROP ON * FROM usr1@host1");
Assert::parse("REVOKE EVENT ON * FROM usr1@host1");
Assert::parse("REVOKE EXECUTE ON * FROM usr1@host1");
Assert::parse("REVOKE FILE ON * FROM usr1@host1");
Assert::parse("REVOKE GRANT OPTION ON * FROM usr1@host1");
Assert::parse("REVOKE INDEX ON * FROM usr1@host1");
Assert::parse("REVOKE INSERT ON * FROM usr1@host1");
Assert::parse("REVOKE LOCK TABLES ON * FROM usr1@host1");
Assert::parse("REVOKE PROCESS ON * FROM usr1@host1");
Assert::parse("REVOKE REFERENCES ON * FROM usr1@host1");
Assert::parse("REVOKE RELOAD ON * FROM usr1@host1");
Assert::parse("REVOKE REPLICATION CLIENT ON * FROM usr1@host1");
Assert::parse("REVOKE REPLICATION SLAVE ON * FROM usr1@host1");
Assert::parse("REVOKE SELECT ON * FROM usr1@host1");
Assert::parse("REVOKE SHOW DATABASES ON * FROM usr1@host1");
Assert::parse("REVOKE SHOW VIEW ON * FROM usr1@host1");
Assert::parse("REVOKE SHUTDOWN ON * FROM usr1@host1");
Assert::parse("REVOKE SUPER ON * FROM usr1@host1");
Assert::parse("REVOKE TRIGGER ON * FROM usr1@host1");
Assert::parse("REVOKE UPDATE ON * FROM usr1@host1");
Assert::parse("REVOKE USAGE ON * FROM usr1@host1");
Assert::parse("REVOKE SELECT, UPDATE ON * FROM usr1@host1");
Assert::parse("REVOKE SELECT (col1), UPDATE (col2, col3) ON * FROM usr1@host1");


// REVOKE ALL [PRIVILEGES], GRANT OPTION FROM user [, user] ...
Assert::parse("REVOKE ALL, GRANT OPTION FROM usr1@host1");
Assert::parse("REVOKE ALL PRIVILEGES, GRANT OPTION FROM usr1@host1", "REVOKE ALL, GRANT OPTION FROM usr1@host1");
Assert::parse("REVOKE ALL, GRANT OPTION FROM usr1@host1, usr2@host2");


// REVOKE PROXY ON user FROM user [, user] ...
Assert::parse("REVOKE PROXY ON usr1@host1 FROM usr2@host2");
Assert::parse("REVOKE PROXY ON usr1@host1 FROM usr2@host2, role3@host3");


// REVOKE role [, role] ... FROM user [, user] ...
Assert::parse("REVOKE admin FROM usr2@host2");
Assert::parse("REVOKE admin, tester FROM usr1@host1");
Assert::parse("REVOKE admin FROM usr2@host2, role3@host3");
