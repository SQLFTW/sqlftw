<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';

// DELETE [LOW_PRIORITY] [QUICK] [IGNORE]
//    FROM tbl_name
Assert::parse("DELETE FROM foo");
Assert::parse("DELETE LOW_PRIORITY FROM foo");
Assert::parse("DELETE QUICK FROM foo");
Assert::parse("DELETE IGNORE FROM foo");
Assert::parse("DELETE LOW_PRIORITY QUICK IGNORE FROM foo");

//    [PARTITION (partition_name, ...)]
Assert::parse("DELETE FROM foo PARTITION (bar, baz)");

//    [WHERE where_condition]
Assert::parse("DELETE FROM foo WHERE bar = 1");

//    [ORDER BY ...]
Assert::parse("DELETE FROM foo ORDER BY bar, baz");

//    [LIMIT row_count]
Assert::parse("DELETE FROM foo LIMIT 10");


// DELETE [LOW_PRIORITY] [QUICK] [IGNORE]
//    tbl_name[.*] [, tbl_name[.*]] ...
//    FROM table_references
Assert::parse("DELETE foo, bar FROM baz", "DELETE FROM foo, bar USING baz");


// DELETE [LOW_PRIORITY] [QUICK] [IGNORE]
//    FROM tbl_name[.*] [, tbl_name[.*]] ...
//    USING table_references
Assert::parse("DELETE FROM foo, bar USING baz");
