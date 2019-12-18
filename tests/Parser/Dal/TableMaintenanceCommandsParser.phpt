<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// ANALYZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE tbl_name [, tbl_name] ...
Assert::parse("ANALYZE TABLE foo");
Assert::parse("ANALYZE TABLE foo, bar");
Assert::parse("ANALYZE NO_WRITE_TO_BINLOG TABLE foo", "ANALYZE LOCAL TABLE foo");
Assert::parse("ANALYZE LOCAL TABLE foo");


// CHECK TABLE tbl_name [, tbl_name] ... [option] ...
Assert::parse("CHECK TABLE foo");
Assert::parse("CHECK TABLE foo, bar");
Assert::parse("CHECK TABLE foo FOR UPGRADE");
Assert::parse("CHECK TABLE foo QUICK");
Assert::parse("CHECK TABLE foo FAST");
Assert::parse("CHECK TABLE foo MEDIUM");
Assert::parse("CHECK TABLE foo EXTENDED");
Assert::parse("CHECK TABLE foo CHANGED");


// CHECKSUM TABLE tbl_name [, tbl_name] ... [QUICK | EXTENDED]
Assert::parse("CHECKSUM TABLE foo");
Assert::parse("CHECKSUM TABLE foo, bar");
Assert::parse("CHECKSUM TABLE foo QUICK");
Assert::parse("CHECKSUM TABLE foo EXTENDED");


// OPTIMIZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE tbl_name [, tbl_name] ...
Assert::parse("OPTIMIZE TABLE foo");
Assert::parse("OPTIMIZE TABLE foo, bar");
Assert::parse("OPTIMIZE NO_WRITE_TO_BINLOG TABLE foo", "OPTIMIZE LOCAL TABLE foo");
Assert::parse("OPTIMIZE LOCAL TABLE foo");


// REPAIR [NO_WRITE_TO_BINLOG | LOCAL] TABLE tbl_name [, tbl_name] ... [QUICK] [EXTENDED] [USE_FRM]
Assert::parse("REPAIR TABLE foo");
Assert::parse("REPAIR TABLE foo, bar");
Assert::parse("REPAIR NO_WRITE_TO_BINLOG TABLE foo", "REPAIR LOCAL TABLE foo");
Assert::parse("REPAIR LOCAL TABLE foo");
Assert::parse("REPAIR TABLE foo QUICK");
Assert::parse("REPAIR TABLE foo QUICK USE_FRM");
Assert::parse("REPAIR TABLE foo QUICK EXTENDED USE_FRM");
