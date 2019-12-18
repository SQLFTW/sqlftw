<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// CREATE USER [IF NOT EXISTS] user [auth_option] [, user [auth_option]] DEFAULT ROLE {NONE | ALL | role [, role ] ...}
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin, developer");
Assert::parse("CREATE USER IF NOT EXISTS 'foo'@'localhost' DEFAULT ROLE admin");

// IDENTIFIED BY 'auth_string' [REPLACE 'current_auth_string'] [RETAIN CURRENT PASSWORD]
Assert::parse("CREATE USER 'foo'@'localhost' IDENTIFIED BY 'bar' DEFAULT ROLE admin");
Assert::parse("CREATE USER 'foo'@'localhost' IDENTIFIED BY 'bar' REPLACE 'baz' DEFAULT ROLE admin");
Assert::parse("CREATE USER 'foo'@'localhost' IDENTIFIED BY 'bar' REPLACE 'baz' RETAIN CURRENT PASSWORD DEFAULT ROLE admin");
Assert::parse("CREATE USER 'foo'@'localhost' IDENTIFIED BY 'bar' RETAIN CURRENT PASSWORD DEFAULT ROLE admin");

// IDENTIFIED WITH auth_plugin
Assert::parse("CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar DEFAULT ROLE admin");

// IDENTIFIED WITH auth_plugin BY 'auth_string' [REPLACE 'current_auth_string'] [RETAIN CURRENT PASSWORD]
Assert::parse("CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' DEFAULT ROLE admin");
Assert::parse("CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' REPLACE 'bak' DEFAULT ROLE admin");
Assert::parse("CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' REPLACE 'bak' RETAIN CURRENT PASSWORD DEFAULT ROLE admin");
Assert::parse("CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' RETAIN CURRENT PASSWORD DEFAULT ROLE admin");

// IDENTIFIED WITH auth_plugin AS 'hash_string'
Assert::parse("CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar AS 'baz' DEFAULT ROLE admin");

// more users
Assert::parse("CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar, 'bar'@'localhost' IDENTIFIED WITH bar DEFAULT ROLE admin");

// [REQUIRE {NONE | tls_option [[AND] tls_option] ...}]
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE NONE");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE SSL");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE X509");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE CIPHER 'bar'");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE ISSUER 'bar'");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE SUBJECT 'bar'");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE SSL AND ISSUER 'bar'");

// [WITH resource_option [resource_option] ...]
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin WITH MAX_QUERIES_PER_HOUR 10");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin WITH MAX_UPDATES_PER_HOUR 10");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin WITH MAX_CONNECTIONS_PER_HOUR 10");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin WITH MAX_USER_CONNECTIONS 10");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin WITH MAX_QUERIES_PER_HOUR 10 MAX_USER_CONNECTIONS 10");

// [password_option | lock_option] ...
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD EXPIRE DEFAULT");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD EXPIRE NEVER");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD EXPIRE INTERVAL 365 DAY");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD HISTORY DEFAULT");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD HISTORY 2");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD REUSE INTERVAL DEFAULT");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD REUSE INTERVAL 365 DAY");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD REQUIRE CURRENT");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD REQUIRE CURRENT DEFAULT");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD REQUIRE CURRENT OPTIONAL");

// ACCOUNT LOCK | ACCOUNT UNLOCK
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin ACCOUNT LOCK");
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin ACCOUNT UNLOCK");

// more
Assert::parse("CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD EXPIRE DEFAULT ACCOUNT UNLOCK");
