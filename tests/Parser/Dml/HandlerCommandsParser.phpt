<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// HANDLER tbl_name OPEN [[AS] alias]
$query = "HANDLER foo OPEN";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo OPEN AS bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// HANDLER tbl_name READ index_name { = | <= | >= | < | > } (value1,value2,...) [WHERE where_condition] [LIMIT ...]
$query = "HANDLER foo READ bar = (1)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ bar <= (1)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ bar >= (1)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ bar < (1)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ bar > (1)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ bar = (1, 2, 3)";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ bar = (1) WHERE baz = 1 AND baz != 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ bar = (1) LIMIT 1 OFFSET 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// HANDLER tbl_name READ index_name { FIRST | NEXT | PREV | LAST } [WHERE where_condition] [LIMIT ...]
$query = "HANDLER foo READ bar FIRST";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ bar NEXT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ bar PREV";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ bar LAST";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ bar FIRST WHERE baz = 1 AND baz != 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ bar FIRST LIMIT 1 OFFSET 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// HANDLER tbl_name READ { FIRST | NEXT } [WHERE where_condition] [LIMIT ...]
$query = "HANDLER foo READ FIRST";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ NEXT";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ FIRST WHERE baz = 1 AND baz != 1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "HANDLER foo READ FIRST LIMIT 1 OFFSET 10";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// HANDLER tbl_name CLOSE
$query = "HANDLER foo CLOSE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
