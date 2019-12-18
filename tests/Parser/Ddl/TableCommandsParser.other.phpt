<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// DROP [TEMPORARY] TABLE [IF EXISTS] tbl_name [, tbl_name] ... [RESTRICT | CASCADE]
Assert::parse("DROP TABLE tbl1");
Assert::parse("DROP TEMPORARY TABLE tbl1");
Assert::parse("DROP TABLE IF EXISTS tbl1");
Assert::parse("DROP TABLE tbl1, tbl2");
Assert::parse("DROP TABLE tbl1 RESTRICT");
Assert::parse("DROP TABLE tbl1 CASCADE");


// RENAME TABLE tbl_name TO new_tbl_name [, tbl_name2 TO new_tbl_name2] ...
Assert::parse("RENAME TABLE tbl1 TO tbl2");
Assert::parse("RENAME TABLE tbl1 TO tbl2, tbl3 TO tbl4");


// TRUNCATE [TABLE] tbl_name
Assert::parse("TRUNCATE TABLE tbl1");
Assert::parse("TRUNCATE tbl1", "TRUNCATE TABLE tbl1");
