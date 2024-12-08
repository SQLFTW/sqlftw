<?php

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// combination of actions, table options and alter options
Assert::parseSerialize("ALTER TABLE tbl1 ADD COLUMN col1 INT");
Assert::parseSerialize("ALTER TABLE tbl1 ADD COLUMN col1 INT, ALGORITHM INSTANT");
Assert::parseSerialize("ALTER TABLE tbl1 ADD COLUMN col1 INT, ENGINE InnoDB");
Assert::parseSerialize("ALTER TABLE tbl1 ADD COLUMN col1 INT, ENGINE InnoDB, ALGORITHM INSTANT");
Assert::parseSerialize("ALTER TABLE tbl1 ENGINE InnoDB");
Assert::parseSerialize("ALTER TABLE tbl1 ENGINE InnoDB, ALGORITHM INSTANT");
Assert::parseSerialize("ALTER TABLE tbl1 ALGORITHM INSTANT");


// DROP [TEMPORARY] TABLE [IF EXISTS] tbl_name [, tbl_name] ... [RESTRICT | CASCADE]
Assert::parseSerialize("DROP TABLE tbl1");
Assert::parseSerialize("DROP TEMPORARY TABLE tbl1");
Assert::parseSerialize("DROP TABLE IF EXISTS tbl1");
Assert::parseSerialize("DROP TABLE tbl1, tbl2");
Assert::parseSerialize("DROP TABLE tbl1 RESTRICT");
Assert::parseSerialize("DROP TABLE tbl1 CASCADE");


// RENAME TABLE tbl_name TO new_tbl_name [, tbl_name2 TO new_tbl_name2] ...
Assert::parseSerialize("RENAME TABLE tbl1 TO tbl2");
Assert::parseSerialize("RENAME TABLE tbl1 TO tbl2, tbl3 TO tbl4");


// TRUNCATE [TABLE] tbl_name
Assert::parseSerialize("TRUNCATE TABLE tbl1");
Assert::parseSerialize("TRUNCATE tbl1", "TRUNCATE TABLE tbl1");
