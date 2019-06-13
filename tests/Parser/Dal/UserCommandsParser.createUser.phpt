<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());


// CREATE USER [IF NOT EXISTS] user [auth_option] [, user [auth_option]] DEFAULT ROLE {NONE | ALL | role [, role ] ...}
$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin, developer";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER IF NOT EXISTS 'foo'@'localhost' DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// IDENTIFIED BY 'auth_string' [REPLACE 'current_auth_string'] [RETAIN CURRENT PASSWORD]
$query = "CREATE USER 'foo'@'localhost' IDENTIFIED BY 'bar' DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' IDENTIFIED BY 'bar' REPLACE 'baz' DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' IDENTIFIED BY 'bar' REPLACE 'baz' RETAIN CURRENT PASSWORD DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' IDENTIFIED BY 'bar' RETAIN CURRENT PASSWORD DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// IDENTIFIED WITH auth_plugin
$query = "CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// IDENTIFIED WITH auth_plugin BY 'auth_string' [REPLACE 'current_auth_string'] [RETAIN CURRENT PASSWORD]
$query = "CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' REPLACE 'bak' DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' REPLACE 'bak' RETAIN CURRENT PASSWORD DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar BY 'baz' RETAIN CURRENT PASSWORD DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// IDENTIFIED WITH auth_plugin AS 'hash_string'
$query = "CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar AS 'baz' DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// more users
$query = "CREATE USER 'foo'@'localhost' IDENTIFIED WITH bar, 'bar'@'localhost' IDENTIFIED WITH bar DEFAULT ROLE admin";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// [REQUIRE {NONE | tls_option [[AND] tls_option] ...}]
$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE NONE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE SSL";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE X509";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE CIPHER 'bar'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE ISSUER 'bar'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE SUBJECT 'bar'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin REQUIRE SSL AND ISSUER 'bar'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// [WITH resource_option [resource_option] ...]
$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin WITH MAX_QUERIES_PER_HOUR 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin WITH MAX_UPDATES_PER_HOUR 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin WITH MAX_CONNECTIONS_PER_HOUR 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin WITH MAX_USER_CONNECTIONS 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin WITH MAX_QUERIES_PER_HOUR 10 MAX_USER_CONNECTIONS 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// [password_option | lock_option] ...
$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD EXPIRE DEFAULT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD EXPIRE NEVER";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD EXPIRE INTERVAL 365 DAY";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD HISTORY DEFAULT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD HISTORY 2";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD REUSE INTERVAL DEFAULT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD REUSE INTERVAL 365 DAY";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD REQUIRE CURRENT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD REQUIRE CURRENT DEFAULT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD REQUIRE CURRENT OPTIONAL";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// ACCOUNT LOCK | ACCOUNT UNLOCK
$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin ACCOUNT LOCK";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin ACCOUNT UNLOCK";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// more
$query = "CREATE USER 'foo'@'localhost' DEFAULT ROLE admin PASSWORD EXPIRE DEFAULT ACCOUNT UNLOCK";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
