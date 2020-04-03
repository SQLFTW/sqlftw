<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// types
Assert::parse("CREATE TABLE tbl1 (
  col_0 BIGINT UNSIGNED,
  col_1 INT,
  col_2 MEDIUMINT,
  col_3 SMALLINT,
  col_4 TINYINT(3),
  col_5 BIT,
  col_6 FLOAT,
  col_7 DOUBLE(10, 2),
  col_8 DECIMAL(10, 2),
  col_9 YEAR,
  col_10 DATE,
  col_11 DATETIME,
  col_12 TIME,
  col_13 TIMESTAMP,
  col_14 CHAR(1) CHARACTER SET 'ascii',
  col_15 VARCHAR(10) COLLATE 'ascii_general_ci',
  col_16 TINYTEXT CHARACTER SET 'ascii' COLLATE 'ascii_general_ci',
  col_17 TEXT,
  col_18 MEDIUMTEXT,
  col_19 LONGTEXT,
  col_20 BINARY(1),
  col_21 VARBINARY(10),
  col_22 TINYBLOB,
  col_23 BLOB,
  col_24 MEDIUMBLOB,
  col_25 LONGBLOB,
  col_28 JSON,
  col_26 ENUM('a', 'b'),
  col_27 SET('c', 'd')
)");

// todo: type aliases

// [NOT NULL | NULL]
Assert::parse("CREATE TABLE tbl1 (col1 INT NOT NULL)");
Assert::parse("CREATE TABLE tbl1 (col1 INT NULL)");

// [DEFAULT default_value]
Assert::parse("CREATE TABLE tbl1 (col1 INT DEFAULT 123)");
Assert::parse("CREATE TABLE tbl1 (col1 INT DEFAULT '123')");
Assert::parse("CREATE TABLE tbl1 (col1 INT DEFAULT NULL)");

// [AUTO_INCREMENT]
Assert::parse("CREATE TABLE tbl1 (col1 INT AUTO_INCREMENT)");

// [UNIQUE [KEY] | [PRIMARY] KEY]
Assert::parse("CREATE TABLE tbl1 (col1 INT PRIMARY KEY)");
Assert::parse("CREATE TABLE tbl1 (col1 INT UNIQUE KEY)");
Assert::parse("CREATE TABLE tbl1 (col1 INT UNIQUE)", "CREATE TABLE tbl1 (col1 INT UNIQUE KEY)");
Assert::parse("CREATE TABLE tbl1 (col1 INT KEY)");

// [COMMENT 'string']
Assert::parse("CREATE TABLE tbl1 (col1 INT COMMENT 'foo')");

// [COLUMN_FORMAT {FIXED|DYNAMIC|DEFAULT}]
Assert::parse("CREATE TABLE tbl1 (col1 INT COLUMN_FORMAT FIXED)");
Assert::parse("CREATE TABLE tbl1 (col1 INT COLUMN_FORMAT DYNAMIC)");
Assert::parse("CREATE TABLE tbl1 (col1 INT COLUMN_FORMAT DEFAULT)");

// [reference_definition]
Assert::parse("CREATE TABLE tbl1 (col1 INT REFERENCES tbl1 (col1))");

// [check_constraint_definition]
Assert::parse("CREATE TABLE tbl1 (col1 INT CHECK (col1 > 0))");


// generated columns
$query = 'CREATE TABLE tbl1 (
  col_0 DATETIME,
  col_1 INT AS (YEAR(col_0)),
  col_2 INT AS (YEAR(col_0)) VIRTUAL,
  col_3 INT AS (YEAR(col_0)) STORED,
  col_4 INT GENERATED ALWAYS AS (YEAR(col_0)),
  col_5 INT GENERATED ALWAYS AS (YEAR(col_0)) VIRTUAL,
  col_6 INT GENERATED ALWAYS AS (YEAR(col_0)) STORED
)';
$result = 'CREATE TABLE tbl1 (
  col_0 DATETIME,
  col_1 INT GENERATED ALWAYS AS (YEAR(col_0)),
  col_2 INT GENERATED ALWAYS AS (YEAR(col_0)) VIRTUAL,
  col_3 INT GENERATED ALWAYS AS (YEAR(col_0)) STORED,
  col_4 INT GENERATED ALWAYS AS (YEAR(col_0)),
  col_5 INT GENERATED ALWAYS AS (YEAR(col_0)) VIRTUAL,
  col_6 INT GENERATED ALWAYS AS (YEAR(col_0)) STORED
)';
Assert::parse($query, $result);
