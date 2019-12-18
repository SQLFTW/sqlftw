<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// GRANT priv_type [(column_list)] [, priv_type [(column_list)]]... ON [object_type] priv_level TO user_or_role [, user_or_role] ...
Assert::parse("GRANT ALL ON * TO 'admin'@'localhost'");
Assert::parse("GRANT ALL ON *.* TO 'admin'@'localhost'");
Assert::parse("GRANT ALL ON foo TO 'admin'@'localhost'");
Assert::parse("GRANT ALL ON foo.* TO 'admin'@'localhost'");
Assert::parse("GRANT ALL ON foo.bar TO 'admin'@'localhost'");
Assert::parse("GRANT ALL ON TABLE foo TO 'admin'@'localhost'");
Assert::parse("GRANT ALL ON FUNCTION foo TO 'admin'@'localhost'");
Assert::parse("GRANT ALL ON PROCEDURE foo TO 'admin'@'localhost'");

// privileges
Assert::parse("GRANT ALTER ON * TO 'admin'@'localhost'");
Assert::parse("GRANT ALTER ROUTINE ON * TO 'admin'@'localhost'");
Assert::parse("GRANT CREATE ON * TO 'admin'@'localhost'");
Assert::parse("GRANT CREATE ROUTINE ON * TO 'admin'@'localhost'");
Assert::parse("GRANT CREATE TABLESPACE ON * TO 'admin'@'localhost'");
Assert::parse("GRANT CREATE TEMPORARY TABLES ON * TO 'admin'@'localhost'");
Assert::parse("GRANT CREATE USER ON * TO 'admin'@'localhost'");
Assert::parse("GRANT CREATE VIEW ON * TO 'admin'@'localhost'");
Assert::parse("GRANT DELETE ON * TO 'admin'@'localhost'");
Assert::parse("GRANT DROP ON * TO 'admin'@'localhost'");
Assert::parse("GRANT EVENT ON * TO 'admin'@'localhost'");
Assert::parse("GRANT EXECUTE ON * TO 'admin'@'localhost'");
Assert::parse("GRANT FILE ON * TO 'admin'@'localhost'");
Assert::parse("GRANT GRANT OPTION ON * TO 'admin'@'localhost'");
Assert::parse("GRANT INDEX ON * TO 'admin'@'localhost'");
Assert::parse("GRANT INSERT ON * TO 'admin'@'localhost'");
Assert::parse("GRANT LOCK TABLES ON * TO 'admin'@'localhost'");
Assert::parse("GRANT PROCESS ON * TO 'admin'@'localhost'");
Assert::parse("GRANT REFERENCES ON * TO 'admin'@'localhost'");
Assert::parse("GRANT RELOAD ON * TO 'admin'@'localhost'");
Assert::parse("GRANT REPLICATION CLIENT ON * TO 'admin'@'localhost'");
Assert::parse("GRANT REPLICATION SLAVE ON * TO 'admin'@'localhost'");
Assert::parse("GRANT SELECT ON * TO 'admin'@'localhost'");
Assert::parse("GRANT SHOW DATABASES ON * TO 'admin'@'localhost'");
Assert::parse("GRANT SHOW VIEW ON * TO 'admin'@'localhost'");
Assert::parse("GRANT SHUTDOWN ON * TO 'admin'@'localhost'");
Assert::parse("GRANT SUPER ON * TO 'admin'@'localhost'");
Assert::parse("GRANT TRIGGER ON * TO 'admin'@'localhost'");
Assert::parse("GRANT UPDATE ON * TO 'admin'@'localhost'");
Assert::parse("GRANT USAGE ON * TO 'admin'@'localhost'");
Assert::parse("GRANT SELECT, UPDATE ON * TO 'admin'@'localhost'");
Assert::parse("GRANT SELECT (col1), UPDATE (col2, col3) ON * TO 'admin'@'localhost'");

// WITH GRANT OPTION
Assert::parse("GRANT ALL ON * TO 'admin'@'localhost' WITH GRANT OPTION");

// AS
Assert::parse("GRANT ALL ON * TO 'admin'@'localhost' AS 'developer'@'localhost'");

// WITH ROLE
Assert::parse("GRANT ALL ON * TO 'admin'@'localhost' AS 'developer'@'localhost' WITH ROLE DEFAULT");
Assert::parse("GRANT ALL ON * TO 'admin'@'localhost' AS 'developer'@'localhost' WITH ROLE NONE");
Assert::parse("GRANT ALL ON * TO 'admin'@'localhost' AS 'developer'@'localhost' WITH ROLE ALL");
Assert::parse("GRANT ALL ON * TO 'admin'@'localhost' AS 'developer'@'localhost' WITH ROLE ALL EXCEPT admin, developer");
Assert::parse("GRANT ALL ON * TO 'admin'@'localhost' AS 'developer'@'localhost' WITH ROLE admin, developer");

// todo: REQUIRE and WITH resource_option from MySQL 5


// GRANT PROXY ON user TO user [, user] ... [WITH GRANT OPTION]
Assert::parse("GRANT PROXY ON 'admin'@'localhost' TO 'developer'@'localhost'");
Assert::parse("GRANT PROXY ON 'admin'@'localhost' TO 'developer'@'localhost', 'tester'@'localhost'");
Assert::parse("GRANT PROXY ON 'admin'@'localhost' TO 'developer'@'localhost' WITH GRANT OPTION");


// GRANT role [, role] ... TO user [, user] ... [WITH ADMIN OPTION]
Assert::parse("GRANT admin TO 'developer'@'localhost'");
Assert::parse("GRANT admin, tester TO 'admin'@'localhost'");
Assert::parse("GRANT admin TO 'developer'@'localhost', 'tester'@'localhost'");
Assert::parse("GRANT admin TO 'developer'@'localhost' WITH ADMIN OPTION");
