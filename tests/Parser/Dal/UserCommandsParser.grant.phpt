<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// GRANT priv_type [(column_list)] [, priv_type [(column_list)]]... ON [object_type] priv_level TO user_or_role [, user_or_role] ...
Assert::parse("GRANT ALL ON * TO 'usr1'@'host1'");
Assert::parse("GRANT ALL ON *.* TO 'usr1'@'host1'");
Assert::parse("GRANT ALL ON db1 TO 'usr1'@'host1'");
Assert::parse("GRANT ALL ON db1.* TO 'usr1'@'host1'");
Assert::parse("GRANT ALL ON db1.tbl1 TO 'usr1'@'host1'");
Assert::parse("GRANT ALL ON TABLE tbl1 TO 'usr1'@'host1'");
Assert::parse("GRANT ALL ON FUNCTION func1 TO 'usr1'@'host1'");
Assert::parse("GRANT ALL ON PROCEDURE proc1 TO 'usr1'@'host1'");

// privileges
Assert::parse("GRANT ALTER ON * TO 'usr1'@'host1'");
Assert::parse("GRANT ALTER ROUTINE ON * TO 'usr1'@'host1'");
Assert::parse("GRANT CREATE ON * TO 'usr1'@'host1'");
Assert::parse("GRANT CREATE ROUTINE ON * TO 'usr1'@'host1'");
Assert::parse("GRANT CREATE TABLESPACE ON * TO 'usr1'@'host1'");
Assert::parse("GRANT CREATE TEMPORARY TABLES ON * TO 'usr1'@'host1'");
Assert::parse("GRANT CREATE USER ON * TO 'usr1'@'host1'");
Assert::parse("GRANT CREATE VIEW ON * TO 'usr1'@'host1'");
Assert::parse("GRANT DELETE ON * TO 'usr1'@'host1'");
Assert::parse("GRANT DROP ON * TO 'usr1'@'host1'");
Assert::parse("GRANT EVENT ON * TO 'usr1'@'host1'");
Assert::parse("GRANT EXECUTE ON * TO 'usr1'@'host1'");
Assert::parse("GRANT FILE ON * TO 'usr1'@'host1'");
Assert::parse("GRANT GRANT OPTION ON * TO 'usr1'@'host1'");
Assert::parse("GRANT INDEX ON * TO 'usr1'@'host1'");
Assert::parse("GRANT INSERT ON * TO 'usr1'@'host1'");
Assert::parse("GRANT LOCK TABLES ON * TO 'usr1'@'host1'");
Assert::parse("GRANT PROCESS ON * TO 'usr1'@'host1'");
Assert::parse("GRANT REFERENCES ON * TO 'usr1'@'host1'");
Assert::parse("GRANT RELOAD ON * TO 'usr1'@'host1'");
Assert::parse("GRANT REPLICATION CLIENT ON * TO 'usr1'@'host1'");
Assert::parse("GRANT REPLICATION SLAVE ON * TO 'usr1'@'host1'");
Assert::parse("GRANT SELECT ON * TO 'usr1'@'host1'");
Assert::parse("GRANT SHOW DATABASES ON * TO 'usr1'@'host1'");
Assert::parse("GRANT SHOW VIEW ON * TO 'usr1'@'host1'");
Assert::parse("GRANT SHUTDOWN ON * TO 'usr1'@'host1'");
Assert::parse("GRANT SUPER ON * TO 'usr1'@'host1'");
Assert::parse("GRANT TRIGGER ON * TO 'usr1'@'host1'");
Assert::parse("GRANT UPDATE ON * TO 'usr1'@'host1'");
Assert::parse("GRANT USAGE ON * TO 'usr1'@'host1'");
Assert::parse("GRANT SELECT, UPDATE ON * TO 'usr1'@'host1'");
Assert::parse("GRANT SELECT (col1), UPDATE (col2, col3) ON * TO 'usr1'@'host1'");

// WITH GRANT OPTION
Assert::parse("GRANT ALL ON * TO 'usr1'@'host1' WITH GRANT OPTION");

// AS
Assert::parse("GRANT ALL ON * TO 'usr1'@'host1' AS 'usr2'@'host2'");

// WITH ROLE
Assert::parse("GRANT ALL ON * TO 'usr1'@'host1' AS 'usr2'@'host2' WITH ROLE DEFAULT");
Assert::parse("GRANT ALL ON * TO 'usr1'@'host1' AS 'usr2'@'host2' WITH ROLE NONE");
Assert::parse("GRANT ALL ON * TO 'usr1'@'host1' AS 'usr2'@'host2' WITH ROLE ALL");
Assert::parse("GRANT ALL ON * TO 'usr1'@'host1' AS 'usr2'@'host2' WITH ROLE ALL EXCEPT role1, role2");
Assert::parse("GRANT ALL ON * TO 'usr1'@'host1' AS 'usr2'@'host2' WITH ROLE role1, role2");

// todo: REQUIRE and WITH resource_option from MySQL 5


// GRANT PROXY ON user TO user [, user] ... [WITH GRANT OPTION]
Assert::parse("GRANT PROXY ON 'usr1'@'host1' TO 'usr2'@'host2'");
Assert::parse("GRANT PROXY ON 'usr1'@'host1' TO 'usr2'@'host2', 'usr3'@'host3'");
Assert::parse("GRANT PROXY ON 'usr1'@'host1' TO 'usr2'@'host2' WITH GRANT OPTION");


// GRANT role [, role] ... TO user [, user] ... [WITH ADMIN OPTION]
Assert::parse("GRANT admin TO 'usr2'@'host2'");
Assert::parse("GRANT admin, tester TO 'usr1'@'host1'");
Assert::parse("GRANT admin TO 'usr2'@'host2', 'usr3'@'host3'");
Assert::parse("GRANT admin TO 'usr2'@'host2' WITH ADMIN OPTION");
