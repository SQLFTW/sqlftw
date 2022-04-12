<?php declare(strict_types = 1);

// phpcs:disable SlevomatCodingStandard.Functions.RequireSingleLineCall

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// all
$query = 'CREATE TABLE test (
  col1 BIGINT,
  col2 CHAR(10),
  col3 CHAR(20),
  PRIMARY KEY (col1),
  UNIQUE INDEX key2 (col2(5), col3(10)),
  INDEX key3 USING HASH (col3)
)';
Assert::parse($query);

// [CONSTRAINT [symbol]] PRIMARY KEY [index_type] (index_col_name, ...) [index_option] ...
Assert::parse("CREATE TABLE tbl1 (col1 INT, PRIMARY KEY (col1))");
Assert::parse("CREATE TABLE tbl1 (col1 INT, CONSTRAINT PRIMARY KEY (col1))");
Assert::parse("CREATE TABLE tbl1 (col1 INT, CONSTRAINT con1 PRIMARY KEY (col1))");

// type & options
Assert::parse("CREATE TABLE tbl1 (col1 INT, PRIMARY KEY USING BTREE (col1))");
Assert::parse(
    "CREATE TABLE tbl1 (col1 INT, PRIMARY KEY (col1) USING BTREE)",
    "CREATE TABLE tbl1 (col1 INT, PRIMARY KEY USING BTREE (col1))"
);
Assert::parse("CREATE TABLE tbl1 (col1 INT, PRIMARY KEY (col1) KEY_BLOCK_SIZE 10)");
Assert::parse("CREATE TABLE tbl1 (col1 INT, PRIMARY KEY (col1) WITH PARSER par1)");
Assert::parse("CREATE TABLE tbl1 (col1 INT, PRIMARY KEY (col1) COMMENT 'com1')");
Assert::parse("CREATE TABLE tbl1 (col1 INT, PRIMARY KEY (col1) VISIBLE)");
Assert::parse("CREATE TABLE tbl1 (col1 INT, PRIMARY KEY (col1) INVISIBLE)");


// [CONSTRAINT [symbol]] UNIQUE [INDEX|KEY] [index_name] [index_type] (index_col_name, ...) [index_option] ...
Assert::parse("CREATE TABLE tbl1 (col1 INT, UNIQUE INDEX (col1))");
Assert::parse(
    "CREATE TABLE tbl1 (col1 INT, UNIQUE KEY (col1))",
    "CREATE TABLE tbl1 (col1 INT, UNIQUE INDEX (col1))"
);
Assert::parse("CREATE TABLE tbl1 (col1 INT, CONSTRAINT UNIQUE INDEX (col1))");
Assert::parse("CREATE TABLE tbl1 (col1 INT, CONSTRAINT con1 UNIQUE INDEX (col1))");


// {INDEX|KEY} [index_name] [index_type] (index_col_name, ...) [index_option] ...
Assert::parse("CREATE TABLE tbl1 (col1 INT, INDEX (col1))");
Assert::parse(
    "CREATE TABLE tbl1 (col1 INT, KEY (col1))",
    "CREATE TABLE tbl1 (col1 INT, INDEX (col1))"
);


// {FULLTEXT|SPATIAL} [INDEX|KEY] [index_name] (index_col_name, ...) [index_option] ...
Assert::parse("CREATE TABLE tbl1 (col1 INT, FULLTEXT INDEX (col1))");
Assert::parse(
    "CREATE TABLE tbl1 (col1 INT, FULLTEXT KEY (col1))",
    "CREATE TABLE tbl1 (col1 INT, FULLTEXT INDEX (col1))"
);

Assert::parse("CREATE TABLE tbl1 (col1 INT, SPATIAL INDEX (col1))");
Assert::parse(
    "CREATE TABLE tbl1 (col1 INT, SPATIAL KEY (col1))",
    "CREATE TABLE tbl1 (col1 INT, SPATIAL INDEX (col1))"
);


// [CONSTRAINT [symbol]] FOREIGN KEY [index_name] (index_col_name, ...) reference_definition
Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1))");
Assert::parse("CREATE TABLE tbl1 (col1 INT, col2 INT, FOREIGN KEY (col1, col2) REFERENCES tbl1 (col1, col2))");
Assert::parse("CREATE TABLE tbl1 (col1 INT, CONSTRAINT FOREIGN KEY (col1) REFERENCES tbl1 (col1))");
Assert::parse("CREATE TABLE tbl1 (col1 INT, CONSTRAINT con1 FOREIGN KEY (col1) REFERENCES tbl1 (col1))");

// options
Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) MATCH FULL)");
Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) MATCH PARTIAL )");
Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) MATCH SIMPLE)");

Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) ON DELETE RESTRICT)");
Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) ON DELETE CASCADE )");
Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) ON DELETE SET NULL)");
Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) ON DELETE NO ACTION)");
Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) ON DELETE SET DEFAULT)");

Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) ON UPDATE RESTRICT)");
Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) ON UPDATE CASCADE )");
Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) ON UPDATE SET NULL)");
Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) ON UPDATE NO ACTION)");
Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) ON UPDATE SET DEFAULT)");

Assert::parse("CREATE TABLE tbl1 (col1 INT, FOREIGN KEY (col1) REFERENCES tbl1 (col1) MATCH FULL ON DELETE RESTRICT ON UPDATE RESTRICT)");


// [CONSTRAINT [symbol]] CHECK (expr) [[NOT] ENFORCED]
Assert::parse("CREATE TABLE tbl1 (col1 INT, CHECK (col1 > 0))");
Assert::parse("CREATE TABLE tbl1 (col1 INT, CHECK (col1 > 0) ENFORCED)");
Assert::parse("CREATE TABLE tbl1 (col1 INT, CHECK (col1 > 0) NOT ENFORCED)");
Assert::parse("CREATE TABLE tbl1 (col1 INT, CONSTRAINT CHECK (col1 > 0))");
Assert::parse("CREATE TABLE tbl1 (col1 INT, CONSTRAINT con1 CHECK (col1 > 0))");
