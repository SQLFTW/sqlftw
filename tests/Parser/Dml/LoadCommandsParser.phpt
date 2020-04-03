<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// LOAD DATA [LOW_PRIORITY | CONCURRENT] [LOCAL] INFILE 'file_name' ...
Assert::parse("LOAD DATA INFILE 'foo' INTO TABLE tbl1");
Assert::parse("LOAD DATA LOW_PRIORITY INFILE 'foo' INTO TABLE tbl1");
Assert::parse("LOAD DATA CONCURRENT INFILE 'foo' INTO TABLE tbl1");
Assert::parse("LOAD DATA LOCAL INFILE 'foo' INTO TABLE tbl1");
Assert::parse("LOAD DATA INFILE 'foo' REPLACE INTO TABLE tbl1");
Assert::parse("LOAD DATA INFILE 'foo' IGNORE INTO TABLE tbl1");
Assert::parse("LOAD DATA INFILE 'foo' INTO TABLE tbl1 PARTITION (p1, p2, p3)");
Assert::parse("LOAD DATA INFILE 'foo' INTO TABLE tbl1 CHARACTER SET 'ascii'");
Assert::parse("LOAD DATA INFILE 'foo' INTO TABLE tbl1 FIELDS TERMINATED BY ';' OPTIONALLY ENCLOSED BY '~' ESCAPED BY '$'");
Assert::parse(
    "LOAD DATA INFILE 'foo' INTO TABLE tbl1 COLUMNS TERMINATED BY ';' OPTIONALLY ENCLOSED BY '~' ESCAPED BY '$'",
    "LOAD DATA INFILE 'foo' INTO TABLE tbl1 FIELDS TERMINATED BY ';' OPTIONALLY ENCLOSED BY '~' ESCAPED BY '$'"
);
Assert::parse("LOAD DATA INFILE 'foo' INTO TABLE tbl1 LINES STARTING BY ';' TERMINATED BY '~'");
Assert::parse("LOAD DATA INFILE 'foo' INTO TABLE tbl1 IGNORE 10 LINES");
Assert::parse(
    "LOAD DATA INFILE 'foo' INTO TABLE tbl1 IGNORE 10 ROWS",
    "LOAD DATA INFILE 'foo' INTO TABLE tbl1 IGNORE 10 LINES"
);
Assert::parse("LOAD DATA INFILE 'foo' INTO TABLE tbl1 (bar, baz)");
Assert::parse("LOAD DATA INFILE 'foo' INTO TABLE tbl1 SET bar = 1, baz = 2");


// LOAD XML [LOW_PRIORITY | CONCURRENT] [LOCAL] INFILE 'file_name'
Assert::parse("LOAD XML INFILE 'foo' INTO TABLE tbl1");
Assert::parse("LOAD XML LOW_PRIORITY INFILE 'foo' INTO TABLE tbl1");
Assert::parse("LOAD XML CONCURRENT INFILE 'foo' INTO TABLE tbl1");
Assert::parse("LOAD XML LOCAL INFILE 'foo' INTO TABLE tbl1");
Assert::parse("LOAD XML INFILE 'foo' REPLACE INTO TABLE tbl1");
Assert::parse("LOAD XML INFILE 'foo' IGNORE INTO TABLE tbl1");
Assert::parse("LOAD XML INFILE 'foo' INTO TABLE tbl1 CHARACTER SET 'ascii'");
Assert::parse("LOAD XML INFILE 'foo' INTO TABLE tbl1 ROWS IDENTIFIED BY '<tr>'");
Assert::parse("LOAD XML INFILE 'foo' INTO TABLE tbl1 IGNORE 10 LINES");
Assert::parse(
    "LOAD XML INFILE 'foo' INTO TABLE tbl1 IGNORE 10 ROWS",
    "LOAD XML INFILE 'foo' INTO TABLE tbl1 IGNORE 10 LINES"
);
Assert::parse("LOAD XML INFILE 'foo' INTO TABLE tbl1 (col1, col2)");
Assert::parse("LOAD XML INFILE 'foo' INTO TABLE tbl1 SET col1 = 1, col2 = 2");
