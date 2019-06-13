<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// LOAD DATA [LOW_PRIORITY | CONCURRENT] [LOCAL] INFILE 'file_name' ...
$query = "LOAD DATA INFILE 'foo' INTO TABLE bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA LOW_PRIORITY INFILE 'foo' INTO TABLE bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA CONCURRENT INFILE 'foo' INTO TABLE bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA LOCAL INFILE 'foo' INTO TABLE bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA INFILE 'foo' REPLACE INTO TABLE bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA INFILE 'foo' IGNORE INTO TABLE bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA INFILE 'foo' INTO TABLE bar PARTITION (p1, p2, p3)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA INFILE 'foo' INTO TABLE bar CHARACTER SET 'ascii'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA INFILE 'foo' INTO TABLE bar FIELDS TERMINATED BY ';' OPTIONALLY ENCLOSED BY '~' ESCAPED BY '$'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA INFILE 'foo' INTO TABLE bar COLUMNS TERMINATED BY ';' OPTIONALLY ENCLOSED BY '~' ESCAPED BY '$'";
$result = "LOAD DATA INFILE 'foo' INTO TABLE bar FIELDS TERMINATED BY ';' OPTIONALLY ENCLOSED BY '~' ESCAPED BY '$'";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA INFILE 'foo' INTO TABLE bar LINES STARTING BY ';' TERMINATED BY '~'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA INFILE 'foo' INTO TABLE bar IGNORE 10 LINES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA INFILE 'foo' INTO TABLE bar IGNORE 10 ROWS";
$result = "LOAD DATA INFILE 'foo' INTO TABLE bar IGNORE 10 LINES";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA INFILE 'foo' INTO TABLE bar IGNORE 10";
$query = "LOAD DATA INFILE 'foo' INTO TABLE bar IGNORE 10 LINES";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA INFILE 'foo' INTO TABLE bar (bar, baz)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD DATA INFILE 'foo' INTO TABLE bar SET bar = 1, baz = 2";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// LOAD XML [LOW_PRIORITY | CONCURRENT] [LOCAL] INFILE 'file_name'
$query = "LOAD XML INFILE 'foo' INTO TABLE bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD XML LOW_PRIORITY INFILE 'foo' INTO TABLE bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD XML CONCURRENT INFILE 'foo' INTO TABLE bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD XML LOCAL INFILE 'foo' INTO TABLE bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD XML INFILE 'foo' REPLACE INTO TABLE bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD XML INFILE 'foo' IGNORE INTO TABLE bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD XML INFILE 'foo' INTO TABLE bar CHARACTER SET 'ascii'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD XML INFILE 'foo' INTO TABLE bar ROWS IDENTIFIED BY '<tr>'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD XML INFILE 'foo' INTO TABLE bar IGNORE 10 LINES";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD XML INFILE 'foo' INTO TABLE bar IGNORE 10 ROWS";
$result = "LOAD XML INFILE 'foo' INTO TABLE bar IGNORE 10 LINES";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD XML INFILE 'foo' INTO TABLE bar IGNORE 10";
$query = "LOAD XML INFILE 'foo' INTO TABLE bar IGNORE 10 LINES";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD XML INFILE 'foo' INTO TABLE bar (bar, baz)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "LOAD XML INFILE 'foo' INTO TABLE bar SET bar = 1, baz = 2";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
