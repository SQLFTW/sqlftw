<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// CREATE [UNIQUE|FULLTEXT|SPATIAL] INDEX index_name [index_type] ON tbl_name (index_col_name, ...) [index_option] [algorithm_option | lock_option] ...
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1)");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1, col2)");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1(10), col2(20))");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1 ASC, col2 DESC, col3(20) ASC)");
Assert::parse("CREATE UNIQUE INDEX idx1 ON tbl1 (col1)");
Assert::parse("CREATE FULLTEXT INDEX idx1 ON tbl1 (col1)");
Assert::parse("CREATE SPATIAL INDEX idx1 ON tbl1 (col1)");

// type
Assert::parse("CREATE INDEX idx1 USING BTREE ON tbl1 (col1)");
Assert::parse("CREATE INDEX idx1 USING HASH ON tbl1 (col1)");

// options
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) KEY_BLOCK_SIZE 10");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) WITH PARSER foo");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) COMMENT 'foo'");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) VISIBLE");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) INVISIBLE");

// ALGORITHM
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) ALGORITHM DEFAULT");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) ALGORITHM COPY");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) ALGORITHM INPLACE");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) ALGORITHM INSTANT");

// LOCK
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) LOCK DEFAULT");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) LOCK NONE");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) LOCK SHARED");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) LOCK EXCLUSIVE");
Assert::parse("CREATE INDEX idx1 ON tbl1 (col1) ALGORITHM INPLACE LOCK NONE");


// DROP INDEX index_name ON tbl_name [algorithm_option | lock_option] ...
Assert::parse("DROP INDEX idx1 ON tbl1");

// ALGORITHM
Assert::parse("DROP INDEX idx1 ON tbl1 ALGORITHM DEFAULT");
Assert::parse("DROP INDEX idx1 ON tbl1 ALGORITHM COPY");
Assert::parse("DROP INDEX idx1 ON tbl1 ALGORITHM INPLACE");
Assert::parse("DROP INDEX idx1 ON tbl1 ALGORITHM INSTANT");

// LOCK
Assert::parse("DROP INDEX idx1 ON tbl1 LOCK DEFAULT");
Assert::parse("DROP INDEX idx1 ON tbl1 LOCK NONE");
Assert::parse("DROP INDEX idx1 ON tbl1 LOCK SHARED");
Assert::parse("DROP INDEX idx1 ON tbl1 LOCK EXCLUSIVE");
