<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';

$parser = ParserHelper::getParserFactory()->getParser();
$formatter = new Formatter($parser->getSettings());

// ADD PARTITION
// DROP PARTITION
// DISCARD PARTITION {partition_names | ALL} TABLESPACE
// IMPORT PARTITION {partition_names | ALL} TABLESPACE
// TRUNCATE PARTITION {partition_names | ALL}
// COALESCE PARTITION number
// REORGANIZE PARTITION partition_names INTO (partition_definitions)
// EXCHANGE PARTITION partition_name WITH TABLE tbl_name [{WITH|WITHOUT} VALIDATION]
// ANALYZE PARTITION {partition_names | ALL}
// CHECK PARTITION {partition_names | ALL}
// OPTIMIZE PARTITION {partition_names | ALL}
// REBUILD PARTITION {partition_names | ALL}
// REPAIR PARTITION {partition_names | ALL}
// REMOVE PARTITIONING
// UPGRADE PARTITIONING

Assert::same(1, 1);
