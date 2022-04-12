<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// AUTO_INCREMENT
Assert::parse("ALTER TABLE tbl1 AUTO_INCREMENT 17");

// AVG_ROW_LENGTH
Assert::parse("ALTER TABLE tbl1 AVG_ROW_LENGTH 17");

// CHARACTER_SET
Assert::parse("ALTER TABLE tbl1 CHARACTER SET utf8");
Assert::parse("ALTER TABLE tbl1 CHARACTER SET 'utf8'", "ALTER TABLE tbl1 CHARACTER SET utf8"); // '...' -> ...
Assert::parse("ALTER TABLE tbl1 CHARSET utf8", "ALTER TABLE tbl1 CHARACTER SET utf8"); // CHARSET -> CHARACTER SET

// CHECKSUM
Assert::parse("ALTER TABLE tbl1 CHECKSUM 0");
Assert::parse("ALTER TABLE tbl1 CHECKSUM 1");

// COLLATE
Assert::parse("ALTER TABLE tbl1 COLLATE ascii_general_ci");
Assert::parse("ALTER TABLE tbl1 COLLATE 'ascii_general_ci'", "ALTER TABLE tbl1 COLLATE ascii_general_ci"); // '...' -> ...

// COMMENT
Assert::parse("ALTER TABLE tbl1 COMMENT 'ciom1'");

// COMPRESSION
Assert::parse("ALTER TABLE tbl1 COMPRESSION 'ZLIB'");
Assert::parse("ALTER TABLE tbl1 COMPRESSION 'LZ4'");
Assert::parse("ALTER TABLE tbl1 COMPRESSION 'NONE'");

// CONNECTION
Assert::parse("ALTER TABLE tbl1 CONNECTION 'con1'");

// DATA_DIRECTORY
Assert::parse("ALTER TABLE tbl1 DATA DIRECTORY 'dir1'");

// DELAY_KEY_WRITE
Assert::parse("ALTER TABLE tbl1 DELAY_KEY_WRITE 0");
Assert::parse("ALTER TABLE tbl1 DELAY_KEY_WRITE 1");

// ENCRYPTION
Assert::parse("ALTER TABLE tbl1 ENCRYPTION 'Y'");
Assert::parse("ALTER TABLE tbl1 ENCRYPTION 'N'");

// ENGINE
Assert::parse("ALTER TABLE tbl1 ENGINE InnoDB");
Assert::parse("ALTER TABLE tbl1 ENGINE 'InnoDB'", "ALTER TABLE tbl1 ENGINE InnoDB"); // '...' -> ...

// INDEX_DIRECTORY
Assert::parse("ALTER TABLE tbl1 INDEX DIRECTORY 'dir1'");

// INSERT_METHOD
Assert::parse("ALTER TABLE tbl1 INSERT_METHOD NO");
Assert::parse("ALTER TABLE tbl1 INSERT_METHOD FIRST");
Assert::parse("ALTER TABLE tbl1 INSERT_METHOD LAST");

// KEY_BLOCK_SIZE
Assert::parse("ALTER TABLE tbl1 KEY_BLOCK_SIZE 17");

// MAX_ROWS
Assert::parse("ALTER TABLE tbl1 MAX_ROWS 17");

// MIN_ROWS
Assert::parse("ALTER TABLE tbl1 MIN_ROWS 17");

// PACK_KEYS
Assert::parse("ALTER TABLE tbl1 PACK_KEYS 0");
Assert::parse("ALTER TABLE tbl1 PACK_KEYS 1");
Assert::parse("ALTER TABLE tbl1 PACK_KEYS DEFAULT");

// PASSWORD
Assert::parse("ALTER TABLE tbl1 PASSWORD 'pwd1'");

// ROW_FORMAT DEFAULT|DYNAMIC|FIXED|COMPRESSED|REDUNDANT|COMPACT
Assert::parse("ALTER TABLE tbl1 ROW_FORMAT DEFAULT");
Assert::parse("ALTER TABLE tbl1 ROW_FORMAT DYNAMIC");
Assert::parse("ALTER TABLE tbl1 ROW_FORMAT FIXED");
Assert::parse("ALTER TABLE tbl1 ROW_FORMAT COMPRESSED");
Assert::parse("ALTER TABLE tbl1 ROW_FORMAT REDUNDANT");
Assert::parse("ALTER TABLE tbl1 ROW_FORMAT COMPACT");

// STATS_AUTO_RECALC
Assert::parse("ALTER TABLE tbl1 STATS_AUTO_RECALC 0");
Assert::parse("ALTER TABLE tbl1 STATS_AUTO_RECALC 1");
Assert::parse("ALTER TABLE tbl1 STATS_AUTO_RECALC DEFAULT");

// STATS_PERSISTENT
Assert::parse("ALTER TABLE tbl1 STATS_PERSISTENT 0");
Assert::parse("ALTER TABLE tbl1 STATS_PERSISTENT 1");
Assert::parse("ALTER TABLE tbl1 STATS_PERSISTENT DEFAULT");

// STATS_SAMPLE_PAGES
Assert::parse("ALTER TABLE tbl1 STATS_SAMPLE_PAGES 17");

// TABLESPACE
Assert::parse("ALTER TABLE tbl1 TABLESPACE 'tbs1'");

// UNION
Assert::parse("ALTER TABLE tbl1 UNION (tbl2, tbl3)");
