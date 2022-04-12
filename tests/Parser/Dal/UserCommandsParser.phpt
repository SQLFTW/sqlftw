<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// CREATE ROLE [IF NOT EXISTS] role [, role ] ...
Assert::parse("CREATE ROLE role1");
Assert::parse("CREATE ROLE role1, role2");
Assert::parse("CREATE ROLE IF NOT EXISTS role1");


// DROP ROLE [IF EXISTS] role [, role ] ...
Assert::parse("DROP ROLE role1");
Assert::parse("DROP ROLE role1, role2");
Assert::parse("DROP ROLE IF EXISTS role1");


// DROP USER [IF EXISTS] user [, user] ...
Assert::parse("DROP USER usr1@host1");
Assert::parse("DROP USER usr1@host1, usr2@host2");
Assert::parse("DROP USER IF EXISTS usr1@host2");


// RENAME USER old_user TO new_user [, old_user TO new_user] ...
Assert::parse("RENAME USER usr1@host1 TO usr2@host2");
Assert::parse("RENAME USER usr1@host1 TO usr2@host2, usr3@host3 TO usr4@host4");


// SET DEFAULT ROLE {NONE | ALL | role [, role ] ...} TO user [, user ] ...
Assert::parse("SET DEFAULT ROLE NONE TO usr1@host1");
Assert::parse("SET DEFAULT ROLE ALL TO usr1@host1");
Assert::parse("SET DEFAULT ROLE role1 TO usr1@host1");
Assert::parse("SET DEFAULT ROLE role1, role2 TO usr1@host1");
Assert::parse("SET DEFAULT ROLE role1 TO usr1@host1, usr2@host2");


// SET PASSWORD [FOR user] = {PASSWORD('auth_string') | 'auth_string'}
Assert::parse("SET PASSWORD = 'pwd1'");
Assert::parse("SET PASSWORD FOR usr1@host1 = 'pwd1'");
Assert::parse("SET PASSWORD = PASSWORD('pwd1')");


// SET ROLE {DEFAULT | NONE | ALL | ALL EXCEPT role [, role ] ... | role [, role ] ... }
Assert::parse("SET ROLE DEFAULT");
Assert::parse("SET ROLE NONE");
Assert::parse("SET ROLE ALL");
Assert::parse("SET ROLE ALL EXCEPT role1");
Assert::parse("SET ROLE ALL EXCEPT role1, role2");
Assert::parse("SET ROLE role1");
Assert::parse("SET ROLE role1, role2");
