<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// {EXPLAIN | DESCRIBE | DESC} tbl_name [col_name | wild]
Assert::parse("DESCRIBE foo");
Assert::parse("DESC foo", "DESCRIBE foo");
Assert::parse("EXPLAIN foo", "DESCRIBE foo");
Assert::parse("DESCRIBE foo bar");
Assert::parse("DESCRIBE foo 'bar%'");


// {EXPLAIN | DESCRIBE | DESC} [explain_type] {explainable_stmt | FOR CONNECTION connection_id}
Assert::parse("EXPLAIN SELECT 1");
Assert::parse("DESCRIBE SELECT 1", "EXPLAIN SELECT 1");
Assert::parse("DESC SELECT 1", "EXPLAIN SELECT 1");
Assert::parse("EXPLAIN EXTENDED SELECT 1");
Assert::parse("EXPLAIN PARTITIONS SELECT 1");
Assert::parse("EXPLAIN FORMAT=TRADITIONAL SELECT 1");
Assert::parse("EXPLAIN FORMAT=JSON SELECT 1");
Assert::parse("EXPLAIN INSERT INTO foo VALUES (1)");
Assert::parse("EXPLAIN REPLACE INTO foo VALUES (1)");
Assert::parse("EXPLAIN DELETE FROM foo");
Assert::parse("EXPLAIN UPDATE foo SET bar = baz");
Assert::parse("EXPLAIN FOR CONNECTION 123");
