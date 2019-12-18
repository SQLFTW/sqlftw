<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// ALTER USER [IF EXISTS] USER() IDENTIFIED BY 'auth_string'
Assert::parse("ALTER USER USER() IDENTIFIED BY 'foo'");
Assert::parse("ALTER USER IF EXISTS USER() IDENTIFIED BY 'foo'");


// ALTER USER [IF EXISTS] user DEFAULT ROLE {NONE | ALL | role [, role ] ...}
Assert::parse("ALTER USER 'foo'@'localhost' DEFAULT ROLE NONE");
Assert::parse("ALTER USER 'foo'@'localhost' DEFAULT ROLE ALL");
Assert::parse("ALTER USER 'foo'@'localhost' DEFAULT ROLE admin");
Assert::parse("ALTER USER 'foo'@'localhost' DEFAULT ROLE admin, developer");
Assert::parse("ALTER USER IF EXISTS 'foo'@'localhost' DEFAULT ROLE NONE");


// ALTER USER [IF EXISTS] user [auth_option] [, user [auth_option]] ...
// IDENTIFIED BY 'auth_string' [REPLACE 'current_auth_string'] [RETAIN CURRENT PASSWORD]
Assert::parse("ALTER USER 'foo'@'localhost' IDENTIFIED BY 'bar'");
Assert::parse("ALTER USER 'foo'@'localhost' IDENTIFIED BY 'bar' REPLACE 'baz'");
Assert::parse("ALTER USER 'foo'@'localhost' IDENTIFIED BY 'bar' REPLACE 'baz' RETAIN CURRENT PASSWORD");
Assert::parse("ALTER USER 'foo'@'localhost' IDENTIFIED BY 'bar' RETAIN CURRENT PASSWORD");

// IDENTIFIED WITH auth_plugin
Assert::parse("ALTER USER 'foo'@'localhost' IDENTIFIED WITH bar");

// IDENTIFIED WITH auth_plugin BY 'auth_string' [REPLACE 'current_auth_string'] [RETAIN CURRENT PASSWORD]
Assert::parse("ALTER USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz'");
Assert::parse("ALTER USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' REPLACE 'bak'");
Assert::parse("ALTER USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' REPLACE 'bak' RETAIN CURRENT PASSWORD");
Assert::parse("ALTER USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' RETAIN CURRENT PASSWORD");

// IDENTIFIED WITH auth_plugin AS 'hash_string'
Assert::parse("ALTER USER 'foo'@'localhost' IDENTIFIED WITH bar AS 'baz'");

// DISCARD OLD PASSWORD
Assert::parse("ALTER USER 'foo'@'localhost' DISCARD OLD PASSWORD");

// more users
Assert::parse("ALTER USER 'foo'@'localhost' DISCARD OLD PASSWORD, 'bar'@'localhost' DISCARD OLD PASSWORD");

// [REQUIRE {NONE | tls_option [[AND] tls_option] ...}]
Assert::parse("ALTER USER 'foo'@'localhost' REQUIRE NONE");
Assert::parse("ALTER USER 'foo'@'localhost' REQUIRE SSL");
Assert::parse("ALTER USER 'foo'@'localhost' REQUIRE X509");
Assert::parse("ALTER USER 'foo'@'localhost' REQUIRE CIPHER 'bar'");
Assert::parse("ALTER USER 'foo'@'localhost' REQUIRE ISSUER 'bar'");
Assert::parse("ALTER USER 'foo'@'localhost' REQUIRE SUBJECT 'bar'");
Assert::parse("ALTER USER 'foo'@'localhost' REQUIRE SSL AND ISSUER 'bar'");

// [WITH resource_option [resource_option] ...]
Assert::parse("ALTER USER 'foo'@'localhost' WITH MAX_QUERIES_PER_HOUR 10");
Assert::parse("ALTER USER 'foo'@'localhost' WITH MAX_UPDATES_PER_HOUR 10");
Assert::parse("ALTER USER 'foo'@'localhost' WITH MAX_CONNECTIONS_PER_HOUR 10");
Assert::parse("ALTER USER 'foo'@'localhost' WITH MAX_USER_CONNECTIONS 10");
Assert::parse("ALTER USER 'foo'@'localhost' WITH MAX_QUERIES_PER_HOUR 10 MAX_USER_CONNECTIONS 10");

// [password_option | lock_option] ...
Assert::parse("ALTER USER 'foo'@'localhost' PASSWORD EXPIRE DEFAULT");
Assert::parse("ALTER USER 'foo'@'localhost' PASSWORD EXPIRE NEVER");
Assert::parse("ALTER USER 'foo'@'localhost' PASSWORD EXPIRE INTERVAL 365 DAY");
Assert::parse("ALTER USER 'foo'@'localhost' PASSWORD HISTORY DEFAULT");
Assert::parse("ALTER USER 'foo'@'localhost' PASSWORD HISTORY 2");
Assert::parse("ALTER USER 'foo'@'localhost' PASSWORD REUSE INTERVAL DEFAULT");
Assert::parse("ALTER USER 'foo'@'localhost' PASSWORD REUSE INTERVAL 365 DAY");
Assert::parse("ALTER USER 'foo'@'localhost' PASSWORD REQUIRE CURRENT");
Assert::parse("ALTER USER 'foo'@'localhost' PASSWORD REQUIRE CURRENT DEFAULT");
Assert::parse("ALTER USER 'foo'@'localhost' PASSWORD REQUIRE CURRENT OPTIONAL");

// ACCOUNT LOCK | ACCOUNT UNLOCK
Assert::parse("ALTER USER 'foo'@'localhost' ACCOUNT LOCK");
Assert::parse("ALTER USER 'foo'@'localhost' ACCOUNT UNLOCK");

// more
Assert::parse("ALTER USER 'foo'@'localhost' PASSWORD EXPIRE DEFAULT ACCOUNT UNLOCK");
