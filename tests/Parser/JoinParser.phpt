<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../bootstrap.php';


// unions vs brackets
Assert::parse("SELECT * FROM SELECT * FROM tbl1 AS ali1"); // subquery without parens
Assert::parse("SELECT * FROM (SELECT * FROM tbl1) AS ali1"); // subquery in parens
Assert::parse("SELECT * FROM ((SELECT * FROM tbl1)) AS ali1"); // subquery in double parens
Assert::parse("SELECT * FROM (((SELECT * FROM tbl1))) AS ali1"); // subquery in triple parens
Assert::parse("SELECT * FROM (((SELECT * FROM tbl1)) AS ali1)"); // parenthesize
Assert::parse("SELECT * FROM (((SELECT * FROM tbl1) AS ali1))");
Assert::parse("SELECT * FROM (((SELECT * FROM tbl1 AS ali1)))");

Assert::parse("SELECT * FROM (SELECT * FROM tbl1 UNION SELECT * FROM tbl2)");
Assert::parse("SELECT * FROM ((SELECT * FROM tbl1 UNION SELECT * FROM tbl2))");
Assert::parse("SELECT * FROM (((SELECT * FROM tbl1 UNION SELECT * FROM tbl2)))");

// unbracketed cascade joins
Assert::parse("SELECT sq2_t1.col_varchar AS sq2_field1
FROM t1 AS sq2_t1 
    STRAIGHT_JOIN t2 AS sq2_t2 
        JOIN t1 AS sq2_t3 ON sq2_t3.col_varchar = sq2_t2.col_varchar_key
    ON sq2_t3.col_int = sq2_t2.pk");
