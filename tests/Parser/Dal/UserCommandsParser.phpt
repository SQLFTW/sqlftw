<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// CREATE ROLE [IF NOT EXISTS] role [, role ] ...
Assert::parse("CREATE ROLE admin");
Assert::parse("CREATE ROLE admin, developer");
Assert::parse("CREATE ROLE IF NOT EXISTS admin");


// DROP ROLE [IF EXISTS] role [, role ] ...
Assert::parse("DROP ROLE admin");
Assert::parse("DROP ROLE admin, developer");
Assert::parse("DROP ROLE IF EXISTS admin");


// DROP USER [IF EXISTS] user [, user] ...
Assert::parse("DROP USER 'admin'@'localhost'");
Assert::parse("DROP USER 'admin'@'localhost', 'developer'@'localhost'");
Assert::parse("DROP USER IF EXISTS 'admin'@'localhost'");


// RENAME USER old_user TO new_user [, old_user TO new_user] ...
Assert::parse("RENAME USER 'admin'@'localhost' TO 'almighty'@'localhost'");
Assert::parse("RENAME USER 'admin'@'localhost' TO 'almighty'@'localhost', 'developer'@'localhost' TO 'lama'@'localhost'");


// SET DEFAULT ROLE {NONE | ALL | role [, role ] ...} TO user [, user ] ...
Assert::parse("SET DEFAULT ROLE NONE TO 'admin'@'localhost'");
Assert::parse("SET DEFAULT ROLE ALL TO 'admin'@'localhost'");
Assert::parse("SET DEFAULT ROLE admin TO 'admin'@'localhost'");
Assert::parse("SET DEFAULT ROLE admin, developer TO 'admin'@'localhost'");
Assert::parse("SET DEFAULT ROLE admin TO 'admin'@'localhost', 'developer'@'localhost'");


// SET PASSWORD [FOR user] = {PASSWORD('auth_string') | 'auth_string'}
Assert::parse("SET PASSWORD = 'foo'");
Assert::parse("SET PASSWORD FOR 'admin'@'localhost' = 'foo'");
Assert::parse("SET PASSWORD = PASSWORD('foo')");


// SET ROLE {DEFAULT | NONE | ALL | ALL EXCEPT role [, role ] ... | role [, role ] ... }
Assert::parse("SET ROLE DEFAULT");
Assert::parse("SET ROLE NONE");
Assert::parse("SET ROLE ALL");
Assert::parse("SET ROLE ALL EXCEPT admin");
Assert::parse("SET ROLE ALL EXCEPT admin, developer");
Assert::parse("SET ROLE admin");
Assert::parse("SET ROLE admin, developer");
