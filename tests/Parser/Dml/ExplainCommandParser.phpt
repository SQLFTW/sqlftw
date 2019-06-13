<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// {EXPLAIN | DESCRIBE | DESC} tbl_name [col_name | wild]
$query = "DESCRIBE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "DESC foo";
$result = "DESCRIBE foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "EXPLAIN foo";
$result = "DESCRIBE foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "DESCRIBE foo bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "DESCRIBE foo 'bar%'";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// {EXPLAIN | DESCRIBE | DESC} [explain_type] {explainable_stmt | FOR CONNECTION connection_id}
$query = "EXPLAIN SELECT 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "DESCRIBE SELECT 1";
$result = "EXPLAIN SELECT 1";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "DESC SELECT 1";
$result = "EXPLAIN SELECT 1";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "EXPLAIN EXTENDED SELECT 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "EXPLAIN PARTITIONS SELECT 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "EXPLAIN FORMAT=TRADITIONAL SELECT 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "EXPLAIN FORMAT=JSON SELECT 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "EXPLAIN INSERT INTO foo VALUES (1)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "EXPLAIN REPLACE INTO foo VALUES (1)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "EXPLAIN DELETE FROM foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "EXPLAIN UPDATE foo SET bar = baz";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "EXPLAIN FOR CONNECTION 123";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
