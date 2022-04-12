<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// {EXPLAIN | DESCRIBE | DESC} tbl_name [col_name | wild]
Assert::parse("DESCRIBE tbl1");
Assert::parse("DESC tbl1", "DESCRIBE tbl1"); // DESC -> DESCRIBE
Assert::parse("EXPLAIN tbl1", "DESCRIBE tbl1"); // EXPLAIN -> DESCRIBE
Assert::parse("DESCRIBE tbl1 tbl2");
Assert::parse("DESCRIBE tbl1 'col1%'");


// {EXPLAIN | DESCRIBE | DESC} [explain_type] {explainable_stmt | FOR CONNECTION connection_id}
Assert::parse("EXPLAIN SELECT 1");
Assert::parse("DESCRIBE SELECT 1", "EXPLAIN SELECT 1");
Assert::parse("DESC SELECT 1", "EXPLAIN SELECT 1");
Assert::parse("EXPLAIN EXTENDED SELECT 1");
Assert::parse("EXPLAIN PARTITIONS SELECT 1");
Assert::parse("EXPLAIN FORMAT=TRADITIONAL SELECT 1");
Assert::parse("EXPLAIN FORMAT=JSON SELECT 1");
Assert::parse("EXPLAIN INSERT INTO tbl1 VALUES (1)");
Assert::parse("EXPLAIN REPLACE INTO tbl1 VALUES (1)");
Assert::parse("EXPLAIN DELETE FROM tbl1");
Assert::parse("EXPLAIN UPDATE tbl1 SET col1 = 1");
Assert::parse("EXPLAIN FOR CONNECTION 123");
