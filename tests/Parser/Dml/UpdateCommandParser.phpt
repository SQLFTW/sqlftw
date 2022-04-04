<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';

// UPDATE [LOW_PRIORITY] [IGNORE] table_reference
//     SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
//     [WHERE where_condition]
//     [ORDER BY ...]
//     [LIMIT row_count]
Assert::parse("UPDATE foo SET bar = 1, baz = 2");
Assert::parse("UPDATE foo SET bar = DEFAULT, baz = DEFAULT");
Assert::parse("UPDATE LOW_PRIORITY foo SET bar = 1, baz = 2");
Assert::parse("UPDATE IGNORE foo SET bar = 1, baz = 2");
Assert::parse("UPDATE LOW_PRIORITY IGNORE foo SET bar = 1, baz = 2");
Assert::parse("UPDATE foo SET bar = 1, baz = 2 WHERE bar = 2");
Assert::parse("UPDATE foo SET bar = 1, baz = 2 ORDER BY foo, bar");
Assert::parse("UPDATE foo SET bar = 1, baz = 2 LIMIT 10");

// UPDATE [LOW_PRIORITY] [IGNORE] table_references
//     SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
//     [WHERE where_condition]
Assert::parse("UPDATE foo JOIN bar ON foo.x = bar.y SET bar = 1, baz = 2");
