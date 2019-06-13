<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// ANALYZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE tbl_name [, tbl_name] ...
$query = "ANALYZE TABLE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ANALYZE TABLE foo, bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "ANALYZE NO_WRITE_TO_BINLOG TABLE foo";
$result = "ANALYZE LOCAL TABLE foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "ANALYZE LOCAL TABLE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// CHECK TABLE tbl_name [, tbl_name] ... [option] ...
$query = "CHECK TABLE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CHECK TABLE foo, bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CHECK TABLE foo FOR UPGRADE";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CHECK TABLE foo QUICK";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CHECK TABLE foo FAST";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CHECK TABLE foo MEDIUM";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CHECK TABLE foo EXTENDED";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CHECK TABLE foo CHANGED";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// CHECKSUM TABLE tbl_name [, tbl_name] ... [QUICK | EXTENDED]
$query = "CHECKSUM TABLE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CHECKSUM TABLE foo, bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CHECKSUM TABLE foo QUICK";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "CHECKSUM TABLE foo EXTENDED";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// OPTIMIZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE tbl_name [, tbl_name] ...
$query = "OPTIMIZE TABLE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "OPTIMIZE TABLE foo, bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "OPTIMIZE NO_WRITE_TO_BINLOG TABLE foo";
$result = "OPTIMIZE LOCAL TABLE foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "OPTIMIZE LOCAL TABLE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));


// REPAIR [NO_WRITE_TO_BINLOG | LOCAL] TABLE tbl_name [, tbl_name] ... [QUICK] [EXTENDED] [USE_FRM]
$query = "REPAIR TABLE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REPAIR TABLE foo, bar";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REPAIR NO_WRITE_TO_BINLOG TABLE foo";
$result = "REPAIR LOCAL TABLE foo";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "REPAIR LOCAL TABLE foo";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REPAIR TABLE foo QUICK";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REPAIR TABLE foo QUICK USE_FRM";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

$query = "REPAIR TABLE foo QUICK EXTENDED USE_FRM";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));
