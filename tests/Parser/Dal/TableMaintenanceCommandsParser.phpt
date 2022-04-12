<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// ANALYZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE tbl_name [, tbl_name] ...
Assert::parse("ANALYZE TABLE tbl1");
Assert::parse("ANALYZE TABLE tbl1, tbl2");
Assert::parse("ANALYZE NO_WRITE_TO_BINLOG TABLE tbl1", "ANALYZE LOCAL TABLE tbl1"); // NO_WRITE_TO_BINLOG -> LOCAL
Assert::parse("ANALYZE LOCAL TABLE tbl1");


// CHECK TABLE tbl_name [, tbl_name] ... [option] ...
Assert::parse("CHECK TABLE tbl1");
Assert::parse("CHECK TABLE tbl1, tbl2");
Assert::parse("CHECK TABLE tbl1 FOR UPGRADE");
Assert::parse("CHECK TABLE tbl1 QUICK");
Assert::parse("CHECK TABLE tbl1 FAST");
Assert::parse("CHECK TABLE tbl1 MEDIUM");
Assert::parse("CHECK TABLE tbl1 EXTENDED");
Assert::parse("CHECK TABLE tbl1 CHANGED");


// CHECKSUM TABLE tbl_name [, tbl_name] ... [QUICK | EXTENDED]
Assert::parse("CHECKSUM TABLE tbl1");
Assert::parse("CHECKSUM TABLE tbl1, tbl2");
Assert::parse("CHECKSUM TABLE tbl1 QUICK");
Assert::parse("CHECKSUM TABLE tbl1 EXTENDED");


// OPTIMIZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE tbl_name [, tbl_name] ...
Assert::parse("OPTIMIZE TABLE tbl1");
Assert::parse("OPTIMIZE TABLE tbl1, tbl2");
Assert::parse("OPTIMIZE NO_WRITE_TO_BINLOG TABLE tbl1", "OPTIMIZE LOCAL TABLE tbl1"); // NO_WRITE_TO_BINLOG -> LOCAL
Assert::parse("OPTIMIZE LOCAL TABLE tbl1");


// REPAIR [NO_WRITE_TO_BINLOG | LOCAL] TABLE tbl_name [, tbl_name] ... [QUICK] [EXTENDED] [USE_FRM]
Assert::parse("REPAIR TABLE tbl1");
Assert::parse("REPAIR TABLE tbl1, tbl2");
Assert::parse("REPAIR NO_WRITE_TO_BINLOG TABLE tbl1", "REPAIR LOCAL TABLE tbl1"); // NO_WRITE_TO_BINLOG -> LOCAL
Assert::parse("REPAIR LOCAL TABLE tbl1");
Assert::parse("REPAIR TABLE tbl1 QUICK");
Assert::parse("REPAIR TABLE tbl1 QUICK USE_FRM");
Assert::parse("REPAIR TABLE tbl1 QUICK EXTENDED");
Assert::parse("REPAIR TABLE tbl1 QUICK EXTENDED USE_FRM");
Assert::parse("REPAIR TABLE tbl1 USE_FRM");
Assert::parse("REPAIR TABLE tbl1 EXTENDED");
Assert::parse("REPAIR TABLE tbl1 EXTENDED USE_FRM");
