<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';

// ALTER VIEW view_name [(column_list)] AS select_statement
Assert::parse("ALTER VIEW view1 AS SELECT 1");
Assert::parse("ALTER VIEW view1 (col1) AS SELECT 1");
Assert::parse("ALTER VIEW view1 (col1, col2) AS SELECT 1");

// [ALGORITHM = {UNDEFINED | MERGE | TEMPTABLE}]
Assert::parse("ALTER ALGORITHM = UNDEFINED VIEW view1 AS SELECT 1");
Assert::parse("ALTER ALGORITHM = MERGE VIEW view1 AS SELECT 1");
Assert::parse("ALTER ALGORITHM = TEMPTABLE VIEW view1 AS SELECT 1");

// [DEFINER = { user | CURRENT_USER }]
Assert::parse("ALTER DEFINER = 'admin'@'localhost' VIEW view1 AS SELECT 1");
Assert::parse("ALTER DEFINER = CURRENT_USER VIEW view1 AS SELECT 1");

// [SQL SECURITY { DEFINER | INVOKER }]
Assert::parse("ALTER SQL SECURITY DEFINER VIEW view1 AS SELECT 1");
Assert::parse("ALTER SQL SECURITY INVOKER VIEW view1 AS SELECT 1");

// [WITH [CASCADED | LOCAL] CHECK OPTION]
Assert::parse("ALTER VIEW view1 AS SELECT 1 WITH CHECK OPTION");
Assert::parse("ALTER VIEW view1 AS SELECT 1 WITH CASCADED CHECK OPTION");
Assert::parse("ALTER VIEW view1 AS SELECT 1 WITH LOCAL CHECK OPTION");


// CREATE [OR REPLACE] VIEW view_name [(column_list)] AS select_statement
Assert::parse("CREATE VIEW view1 AS SELECT 1");
Assert::parse("CREATE VIEW view1 (col1) AS SELECT 1");
Assert::parse("CREATE VIEW view1 (col1, col2) AS SELECT 1");
Assert::parse("CREATE OR REPLACE VIEW view1 AS SELECT 1");

// [ALGORITHM = {UNDEFINED | MERGE | TEMPTABLE}]
Assert::parse("CREATE ALGORITHM = UNDEFINED VIEW view1 AS SELECT 1");
Assert::parse("CREATE ALGORITHM = MERGE VIEW view1 AS SELECT 1");
Assert::parse("CREATE ALGORITHM = TEMPTABLE VIEW view1 AS SELECT 1");

// [DEFINER = { user | CURRENT_USER }]
Assert::parse("CREATE DEFINER = 'admin'@'localhost' VIEW view1 AS SELECT 1");
Assert::parse("CREATE DEFINER = CURRENT_USER VIEW view1 AS SELECT 1");

// [SQL SECURITY { DEFINER | INVOKER }]
Assert::parse("CREATE SQL SECURITY DEFINER VIEW view1 AS SELECT 1");
Assert::parse("CREATE SQL SECURITY INVOKER VIEW view1 AS SELECT 1");

// [WITH [CASCADED | LOCAL] CHECK OPTION]
Assert::parse("CREATE VIEW view1 AS SELECT 1 WITH CHECK OPTION");
Assert::parse("CREATE VIEW view1 AS SELECT 1 WITH CASCADED CHECK OPTION");
Assert::parse("CREATE VIEW view1 AS SELECT 1 WITH LOCAL CHECK OPTION");


// DROP VIEW [IF EXISTS] view_name [, view_name] ... [RESTRICT | CASCADE]
Assert::parse("DROP VIEW view1");
Assert::parse("DROP VIEW IF EXISTS view1");
Assert::parse("DROP VIEW view1, view2");
Assert::parse("DROP VIEW view1 RESTRICT");
Assert::parse("DROP VIEW view1 CASCADE");
