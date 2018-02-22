<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// CACHE INDEX
$query = "CACHE INDEX table1 INDEX (index1, index2), table2 KEY (index3) PARTITION (partition1, partition2) IN keyCache1";
$result = "CACHE INDEX table1 INDEX (index1, index2), table2 INDEX (index3) PARTITION (partition1, partition2) IN keyCache1";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));

$query = "CACHE INDEX table1 INDEX (index1) PARTITION (ALL) IN keyCache1";
Assert::same($query, $parser->parseCommand($query)->serialize($formatter));

// LOAD INDEX INTO CACHE
$query = "LOAD INDEX INTO CACHE table1 PARTITION (partition1, partition2) INDEX (index1, index2), table2 PARTITION (ALL) KEY (index3) IGNORE LEAVES";
$result = "LOAD INDEX INTO CACHE table1 PARTITION (partition1, partition2) INDEX (index1, index2), table2 PARTITION (ALL) INDEX (index3) IGNORE LEAVES";
Assert::same($result, $parser->parseCommand($query)->serialize($formatter));
