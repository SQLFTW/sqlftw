<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// ADD COLUMN {FIRST|AFTER}
Assert::parse("ALTER TABLE tbl1 ADD COLUMN col1 INT");
Assert::parse("ALTER TABLE tbl1 ADD COLUMN col1 INT FIRST");
Assert::parse("ALTER TABLE tbl1 ADD COLUMN col1 INT AFTER col1");

// ADD INDEX|KEY
Assert::parse("ALTER TABLE tbl1 ADD INDEX key1 (col1)");
Assert::parse("ALTER TABLE tbl1 ADD KEY key1 (col1)", "ALTER TABLE tbl1 ADD INDEX key1 (col1)");

// ADD [CONSTRAINT] PRIMARY KEY
Assert::parse("ALTER TABLE tbl1 ADD PRIMARY KEY (col1)");
Assert::parse("ALTER TABLE tbl1 ADD CONSTRAINT foo PRIMARY KEY (col1)");

// ADD [CONSTRAINT] UNIQUE {INDEX|KEY}
Assert::parse("ALTER TABLE tbl1 ADD UNIQUE INDEX (col1)");
Assert::parse("ALTER TABLE tbl1 ADD UNIQUE KEY (col1)", "ALTER TABLE tbl1 ADD UNIQUE INDEX (col1)");
Assert::parse("ALTER TABLE tbl1 ADD CONSTRAINT con1 UNIQUE INDEX (col1)");

// ADD FULLTEXT [INDEX|KEY]
Assert::parse("ALTER TABLE tbl1 ADD FULLTEXT INDEX (col1)");
Assert::parse("ALTER TABLE tbl1 ADD FULLTEXT KEY (col1)", "ALTER TABLE tbl1 ADD FULLTEXT INDEX (col1)");

// ADD SPATIAL [INDEX|KEY]
Assert::parse("ALTER TABLE tbl1 ADD SPATIAL INDEX (col1)");
Assert::parse("ALTER TABLE tbl1 ADD SPATIAL KEY (col1)", "ALTER TABLE tbl1 ADD SPATIAL INDEX (col1)");

// ADD [CONSTRAINT] FOREIGN KEY
Assert::parse("ALTER TABLE tbl1 ADD FOREIGN KEY (fk1) REFERENCES table2 (col1)");
Assert::parse("ALTER TABLE tbl1 ADD CONSTRAINT fk1 FOREIGN KEY (col1) REFERENCES table2 (col1)");

// ALTER [COLUMN]
Assert::parse("ALTER TABLE tbl1 ALTER COLUMN col1 SET DEFAULT 1");
Assert::parse("ALTER TABLE tbl1 ALTER col1 DROP DEFAULT", "ALTER TABLE tbl1 ALTER COLUMN col1 DROP DEFAULT");

// CHANGE [COLUMN]
Assert::parse("ALTER TABLE tbl1 CHANGE COLUMN col1 bar INT");
Assert::parse("ALTER TABLE tbl1 CHANGE col1 col2 INT", "ALTER TABLE tbl1 CHANGE COLUMN col1 col2 INT");

// MODIFY [COLUMN]
Assert::parse("ALTER TABLE tbl1 MODIFY COLUMN col1 INT");
Assert::parse("ALTER TABLE tbl1 MODIFY COLUMN col1 INT FIRST");
Assert::parse("ALTER TABLE tbl1 MODIFY COLUMN col1 INT AFTER col2");
Assert::parse("ALTER TABLE tbl1 MODIFY col1 INT", "ALTER TABLE tbl1 MODIFY COLUMN col1 INT");

// DROP [COLUMN]
Assert::parse("ALTER TABLE tbl1 DROP COLUMN col1");
Assert::parse("ALTER TABLE tbl1 DROP col1", "ALTER TABLE tbl1 DROP COLUMN col1");

// DROP PRIMARY KEY
Assert::parse("ALTER TABLE tbl1 DROP PRIMARY KEY");

// DROP {INDEX|KEY}
Assert::parse("ALTER TABLE tbl1 DROP INDEX key1");
Assert::parse("ALTER TABLE tbl1 DROP KEY key1", "ALTER TABLE tbl1 DROP INDEX key1");

// DROP FOREIGN KEY
Assert::parse("ALTER TABLE tbl1 DROP FOREIGN KEY fk1");

// ALTER INDEX (MySQL 8.0+)
Assert::parse("ALTER TABLE tbl1 ALTER INDEX key1 VISIBLE");
Assert::parse("ALTER TABLE tbl1 ALTER INDEX key1 INVISIBLE");

// DISABLE KEYS
Assert::parse("ALTER TABLE tbl1 DISABLE KEYS");

// ENABLE KEYS
Assert::parse("ALTER TABLE tbl1 ENABLE KEYS");

// RENAME TO
Assert::parse("ALTER TABLE tbl1 RENAME TO bar");

// RENAME {INDEX|KEY}
Assert::parse("ALTER TABLE tbl1 RENAME INDEX key1 TO key2");

// ORDER BY
Assert::parse("ALTER TABLE tbl1 ORDER BY col1");
Assert::parse("ALTER TABLE tbl1 ORDER BY col1, col2");

// CONVERT TO CHARACTER SET
Assert::parse("ALTER TABLE tbl1 CONVERT TO CHARACTER SET ascii");
Assert::parse("ALTER TABLE tbl1 CONVERT TO CHARACTER SET 'ascii'", "ALTER TABLE tbl1 CONVERT TO CHARACTER SET ascii"); // '...' -> ...
Assert::parse("ALTER TABLE tbl1 CONVERT TO CHARACTER SET ascii COLLATE ascii_general_ci");
Assert::parse(
    "ALTER TABLE tbl1 CONVERT TO CHARACTER SET 'ascii' COLLATE 'ascii_general_ci'",
    "ALTER TABLE tbl1 CONVERT TO CHARACTER SET ascii COLLATE ascii_general_ci" // '...' -> ...
);

// DISCARD TABLESPACE
Assert::parse("ALTER TABLE tbl1 DISCARD TABLESPACE");

// IMPORT TABLESPACE
Assert::parse("ALTER TABLE tbl1 IMPORT TABLESPACE");
