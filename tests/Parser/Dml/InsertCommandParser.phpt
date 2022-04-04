<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';

// INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
//     [INTO] tbl_name
//     [PARTITION (partition_name, ...)]
//     [(col_name, ...)]
//     {VALUES | VALUE} ({expr | DEFAULT}, ...), (...), ...
//     [ ON DUPLICATE KEY UPDATE
//       col_name=expr [, col_name=expr] ... ]
Assert::parse("INSERT INTO foo VALUES (1, 2)");
Assert::parse("INSERT INTO foo VALUES (DEFAULT, DEFAULT)");
Assert::parse("INSERT foo VALUES (1, 2)", "INSERT INTO foo VALUES (1, 2)");
Assert::parse("INSERT LOW_PRIORITY INTO foo VALUES (1, 2)");
Assert::parse("INSERT DELAYED INTO foo VALUES (1, 2)");
Assert::parse("INSERT HIGH_PRIORITY INTO foo VALUES (1, 2)");
Assert::parse("INSERT IGNORE INTO foo VALUES (1, 2)");
Assert::parse("INSERT DELAYED IGNORE INTO foo VALUES (1, 2)");

Assert::parse("INSERT INTO foo PARTITION (bar, baz) VALUES (1, 2)");
Assert::parse("INSERT INTO foo VALUES (1, 2) ON DUPLICATE KEY UPDATE bar = 1, baz = 2");

//     SET col_name={expr | DEFAULT}, ...
Assert::parse("INSERT INTO foo SET bar = 1, baz = 2");
Assert::parse("INSERT INTO foo SET bar = DEFAULT, baz = DEFAULT");

//     SELECT ...
Assert::parse("INSERT INTO foo SELECT * FROM bar");


// REPLACE [LOW_PRIORITY | DELAYED]
//     [INTO] tbl_name
//     [PARTITION (partition_name, ...)]
//     [(col_name, ...)]
//     {VALUES | VALUE} ({expr | DEFAULT}, ...), (...), ...
Assert::parse("REPLACE INTO foo VALUES (1, 2)");
Assert::parse("REPLACE INTO foo VALUES (DEFAULT, DEFAULT)");
Assert::parse("REPLACE foo VALUES (1, 2)", "REPLACE INTO foo VALUES (1, 2)");
Assert::parse("REPLACE LOW_PRIORITY INTO foo VALUES (1, 2)");
Assert::parse("REPLACE DELAYED INTO foo VALUES (1, 2)");

Assert::parse("REPLACE INTO foo PARTITION (bar, baz) VALUES (1, 2)");

//     SET col_name={expr | DEFAULT}, ...
Assert::parse("REPLACE INTO foo SET bar = 1, baz = 2");
Assert::parse("REPLACE INTO foo SET bar = DEFAULT, baz = DEFAULT");

//     SELECT ...
Assert::parse("REPLACE INTO foo SELECT * FROM bar");