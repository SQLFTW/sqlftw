<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// more
Assert::parse("CREATE TABLE tbl1 (col1 INT) AUTO_INCREMENT 17, AVG_ROW_LENGTH 17");

// AUTO_INCREMENT
Assert::parse("CREATE TABLE tbl1 (col1 INT) AUTO_INCREMENT 17");

// AVG_ROW_LENGTH
Assert::parse("CREATE TABLE tbl1 (col1 INT) AVG_ROW_LENGTH 17");

// CHARACTER_SET
Assert::parse("CREATE TABLE tbl1 (col1 INT) CHARACTER SET 'utf8'");

// CHECKSUM
Assert::parse("CREATE TABLE tbl1 (col1 INT) CHECKSUM 0");
Assert::parse("CREATE TABLE tbl1 (col1 INT) CHECKSUM 1");

// COLLATE
Assert::parse("CREATE TABLE tbl1 (col1 INT) COLLATE 'ascii_general_ci'");

// COMMENT
Assert::parse("CREATE TABLE tbl1 (col1 INT) COMMENT 'foo'");

// COMPRESSION
Assert::parse("CREATE TABLE tbl1 (col1 INT) COMPRESSION 'ZLIB'");
Assert::parse("CREATE TABLE tbl1 (col1 INT) COMPRESSION 'LZ4'");
Assert::parse("CREATE TABLE tbl1 (col1 INT) COMPRESSION 'NONE'");

// CONNECTION
Assert::parse("CREATE TABLE tbl1 (col1 INT) CONNECTION 'foo'");

// DATA_DIRECTORY
Assert::parse("CREATE TABLE tbl1 (col1 INT) DATA DIRECTORY 'foo'");

// DELAY_KEY_WRITE
Assert::parse("CREATE TABLE tbl1 (col1 INT) DELAY_KEY_WRITE 0");
Assert::parse("CREATE TABLE tbl1 (col1 INT) DELAY_KEY_WRITE 1");

// ENCRYPTION
Assert::parse("CREATE TABLE tbl1 (col1 INT) ENCRYPTION 'Y'");
Assert::parse("CREATE TABLE tbl1 (col1 INT) ENCRYPTION 'N'");

// ENGINE
Assert::parse("CREATE TABLE tbl1 (col1 INT) ENGINE 'InnoDB'");

// INDEX_DIRECTORY
Assert::parse("CREATE TABLE tbl1 (col1 INT) INDEX DIRECTORY 'foo'");

// INSERT_METHOD
Assert::parse("CREATE TABLE tbl1 (col1 INT) INSERT_METHOD NO");
Assert::parse("CREATE TABLE tbl1 (col1 INT) INSERT_METHOD FIRST");
Assert::parse("CREATE TABLE tbl1 (col1 INT) INSERT_METHOD LAST");

// KEY_BLOCK_SIZE
Assert::parse("CREATE TABLE tbl1 (col1 INT) KEY_BLOCK_SIZE 17");

// MAX_ROWS
Assert::parse("CREATE TABLE tbl1 (col1 INT) MAX_ROWS 17");

// MIN_ROWS
Assert::parse("CREATE TABLE tbl1 (col1 INT) MIN_ROWS 17");

// PACK_KEYS
Assert::parse("CREATE TABLE tbl1 (col1 INT) PACK_KEYS 0");
Assert::parse("CREATE TABLE tbl1 (col1 INT) PACK_KEYS 1");
Assert::parse("CREATE TABLE tbl1 (col1 INT) PACK_KEYS DEFAULT");

// PASSWORD
Assert::parse("CREATE TABLE tbl1 (col1 INT) PASSWORD 'secret'");

// ROW_FORMAT DEFAULT|DYNAMIC|FIXED|COMPRESSED|REDUNDANT|COMPACT
Assert::parse("CREATE TABLE tbl1 (col1 INT) ROW_FORMAT DEFAULT");
Assert::parse("CREATE TABLE tbl1 (col1 INT) ROW_FORMAT DYNAMIC");
Assert::parse("CREATE TABLE tbl1 (col1 INT) ROW_FORMAT FIXED");
Assert::parse("CREATE TABLE tbl1 (col1 INT) ROW_FORMAT COMPRESSED");
Assert::parse("CREATE TABLE tbl1 (col1 INT) ROW_FORMAT REDUNDANT");
Assert::parse("CREATE TABLE tbl1 (col1 INT) ROW_FORMAT COMPACT");

// STATS_AUTO_RECALC
Assert::parse("CREATE TABLE tbl1 (col1 INT) STATS_AUTO_RECALC 0");
Assert::parse("CREATE TABLE tbl1 (col1 INT) STATS_AUTO_RECALC 1");
Assert::parse("CREATE TABLE tbl1 (col1 INT) STATS_AUTO_RECALC DEFAULT");

// STATS_PERSISTENT
Assert::parse("CREATE TABLE tbl1 (col1 INT) STATS_PERSISTENT 0");
Assert::parse("CREATE TABLE tbl1 (col1 INT) STATS_PERSISTENT 1");
Assert::parse("CREATE TABLE tbl1 (col1 INT) STATS_PERSISTENT DEFAULT");

// STATS_SAMPLE_PAGES
Assert::parse("CREATE TABLE tbl1 (col1 INT) STATS_SAMPLE_PAGES 17");

// TABLESPACE
Assert::parse("CREATE TABLE tbl1 (col1 INT) TABLESPACE 'foo'");

// UNION
Assert::parse("CREATE TABLE tbl1 (col1 INT) UNION (tbl2, tbl3)");
