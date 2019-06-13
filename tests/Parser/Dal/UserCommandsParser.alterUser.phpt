<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// ALTER USER [IF EXISTS] USER() IDENTIFIED BY 'auth_string'
$query = "ALTER USER USER() IDENTIFIED BY 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER IF EXISTS USER() IDENTIFIED BY 'foo'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// ALTER USER [IF EXISTS] user DEFAULT ROLE {NONE | ALL | role [, role ] ...}
$query = "ALTER USER 'foo'@'localhost' DEFAULT ROLE NONE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' DEFAULT ROLE ALL";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' DEFAULT ROLE admin, developer";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER IF EXISTS 'foo'@'localhost' DEFAULT ROLE NONE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// ALTER USER [IF EXISTS] user [auth_option] [, user [auth_option]] ...

// IDENTIFIED BY 'auth_string' [REPLACE 'current_auth_string'] [RETAIN CURRENT PASSWORD]
$query = "ALTER USER 'foo'@'localhost' IDENTIFIED BY 'bar'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' IDENTIFIED BY 'bar' REPLACE 'baz'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' IDENTIFIED BY 'bar' REPLACE 'baz' RETAIN CURRENT PASSWORD";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' IDENTIFIED BY 'bar' RETAIN CURRENT PASSWORD";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// IDENTIFIED WITH auth_plugin
$query = "ALTER USER 'foo'@'localhost' IDENTIFIED WITH bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// IDENTIFIED WITH auth_plugin BY 'auth_string' [REPLACE 'current_auth_string'] [RETAIN CURRENT PASSWORD]
$query = "ALTER USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' REPLACE 'bak'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' REPLACE 'bak' RETAIN CURRENT PASSWORD";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' RETAIN CURRENT PASSWORD";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// IDENTIFIED WITH auth_plugin AS 'hash_string'
$query = "ALTER USER 'foo'@'localhost' IDENTIFIED WITH bar AS 'baz'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// DISCARD OLD PASSWORD
$query = "ALTER USER 'foo'@'localhost' DISCARD OLD PASSWORD";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// more users
$query = "ALTER USER 'foo'@'localhost' DISCARD OLD PASSWORD, 'bar'@'localhost' DISCARD OLD PASSWORD";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// [REQUIRE {NONE | tls_option [[AND] tls_option] ...}]
$query = "ALTER USER 'foo'@'localhost' REQUIRE NONE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' REQUIRE SSL";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' REQUIRE X509";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' REQUIRE CIPHER 'bar'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' REQUIRE ISSUER 'bar'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' REQUIRE SUBJECT 'bar'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' REQUIRE SSL AND ISSUER 'bar'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// [WITH resource_option [resource_option] ...]
$query = "ALTER USER 'foo'@'localhost' WITH MAX_QUERIES_PER_HOUR 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' WITH MAX_UPDATES_PER_HOUR 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' WITH MAX_CONNECTIONS_PER_HOUR 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' WITH MAX_USER_CONNECTIONS 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' WITH MAX_QUERIES_PER_HOUR 10 MAX_USER_CONNECTIONS 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// [password_option | lock_option] ...
$query = "ALTER USER 'foo'@'localhost' PASSWORD EXPIRE DEFAULT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' PASSWORD EXPIRE NEVER";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' PASSWORD EXPIRE INTERVAL 365 DAY";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' PASSWORD HISTORY DEFAULT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' PASSWORD HISTORY 2";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' PASSWORD REUSE INTERVAL DEFAULT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' PASSWORD REUSE INTERVAL 365 DAY";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' PASSWORD REQUIRE CURRENT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' PASSWORD REQUIRE CURRENT DEFAULT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' PASSWORD REQUIRE CURRENT OPTIONAL";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// ACCOUNT LOCK | ACCOUNT UNLOCK
$query = "ALTER USER 'foo'@'localhost' ACCOUNT LOCK";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ALTER USER 'foo'@'localhost' ACCOUNT UNLOCK";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// more
$query = "ALTER USER 'foo'@'localhost' PASSWORD EXPIRE DEFAULT ACCOUNT UNLOCK";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
