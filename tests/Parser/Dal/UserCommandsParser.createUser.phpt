<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// CREATE USER [IF NOT EXISTS] user [auth_option] [, user [auth_option]] DEFAULT ROLE {NONE | ALL | role [, role ] ...}
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1, role2");
Assert::parse("CREATE USER IF NOT EXISTS usr1@host1 DEFAULT ROLE role1");

// IDENTIFIED BY 'auth_string' [REPLACE 'current_auth_string'] [RETAIN CURRENT PASSWORD]
Assert::parse("CREATE USER usr1@host1 IDENTIFIED BY 'auth1' DEFAULT ROLE admin");
Assert::parse("CREATE USER usr1@host1 IDENTIFIED BY 'auth1' REPLACE 'auth2' DEFAULT ROLE role1");
Assert::parse("CREATE USER usr1@host1 IDENTIFIED BY 'auth1' REPLACE 'auth2' RETAIN CURRENT PASSWORD DEFAULT ROLE role1");
Assert::parse("CREATE USER usr1@host1 IDENTIFIED BY 'auth1' RETAIN CURRENT PASSWORD DEFAULT ROLE role1");

// IDENTIFIED WITH auth_plugin
Assert::parse("CREATE USER usr1@host1 IDENTIFIED WITH plug1 DEFAULT ROLE admin");

// IDENTIFIED WITH auth_plugin BY 'auth_string' [REPLACE 'current_auth_string'] [RETAIN CURRENT PASSWORD]
Assert::parse("CREATE USER usr1@host1 IDENTIFIED WITH plug1 BY 'auth1' DEFAULT ROLE admin");
Assert::parse("CREATE USER usr1@host1 IDENTIFIED WITH plug1 BY 'auth1' REPLACE 'auth2' DEFAULT ROLE role1");
Assert::parse("CREATE USER usr1@host1 IDENTIFIED WITH plug1 BY 'auth1' REPLACE 'auth2' RETAIN CURRENT PASSWORD DEFAULT ROLE role1");
Assert::parse("CREATE USER usr1@host1 IDENTIFIED WITH plug1 BY 'auth1' RETAIN CURRENT PASSWORD DEFAULT ROLE role1");

// IDENTIFIED WITH auth_plugin AS 'hash_string'
Assert::parse("CREATE USER usr1@host1 IDENTIFIED WITH plug1 AS 'hash1' DEFAULT ROLE role1");

// more users
Assert::parse("CREATE USER usr1@host1 IDENTIFIED WITH plug1, usr2@host2 IDENTIFIED WITH plug1 DEFAULT ROLE role1");

// [REQUIRE {NONE | tls_option [[AND] tls_option] ...}]
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 REQUIRE NONE");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 REQUIRE SSL");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 REQUIRE X509");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 REQUIRE CIPHER 'cipher1'");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 REQUIRE ISSUER 'issuer1'");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 REQUIRE SUBJECT 'subject1'");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 REQUIRE SSL AND ISSUER 'issuer1'");

// [WITH resource_option [resource_option] ...]
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 WITH MAX_QUERIES_PER_HOUR 10");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 WITH MAX_UPDATES_PER_HOUR 10");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 WITH MAX_CONNECTIONS_PER_HOUR 10");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 WITH MAX_USER_CONNECTIONS 10");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 WITH MAX_QUERIES_PER_HOUR 10 MAX_USER_CONNECTIONS 10");

// [password_option | lock_option] ...
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 PASSWORD EXPIRE DEFAULT");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 PASSWORD EXPIRE NEVER");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 PASSWORD EXPIRE INTERVAL 365 DAY");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 PASSWORD HISTORY DEFAULT");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 PASSWORD HISTORY 2");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 PASSWORD REUSE INTERVAL DEFAULT");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 PASSWORD REUSE INTERVAL 365 DAY");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 PASSWORD REQUIRE CURRENT");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 PASSWORD REQUIRE CURRENT DEFAULT");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 PASSWORD REQUIRE CURRENT OPTIONAL");

// ACCOUNT LOCK | ACCOUNT UNLOCK
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 ACCOUNT LOCK");
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 ACCOUNT UNLOCK");

// more
Assert::parse("CREATE USER usr1@host1 DEFAULT ROLE role1 PASSWORD EXPIRE DEFAULT ACCOUNT UNLOCK");
