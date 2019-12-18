<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// COMMIT [WORK] [AND [NO] CHAIN] [[NO] RELEASE]
Assert::parse("COMMIT");
Assert::parse("COMMIT WORK", "COMMIT");
Assert::parse("COMMIT AND CHAIN");
Assert::parse("COMMIT AND NO CHAIN");
Assert::parse("COMMIT RELEASE");
Assert::parse("COMMIT NO RELEASE");


// LOCK TABLES tbl_name [[AS] alias] lock_type [, tbl_name [[AS] alias] lock_type] ...
Assert::parse("LOCK TABLES tbl1");
Assert::parse("LOCK TABLES tbl1 AS foo1");
Assert::parse("LOCK TABLES tbl1, tbl2");
Assert::parse("LOCK TABLES tbl1 AS foo, tbl2 AS bar");
Assert::parse("LOCK TABLES tbl1 AS foo READ, tbl2 AS bar READ LOCAL");
Assert::parse("LOCK TABLES tbl1 AS foo WRITE, tbl2 AS bar LOW_PRIORITY WRITE");


// RELEASE SAVEPOINT identifier
Assert::parse("RELEASE SAVEPOINT svp1");


// ROLLBACK [WORK] [AND [NO] CHAIN] [[NO] RELEASE]
Assert::parse("ROLLBACK");
Assert::parse("ROLLBACK WORK", "ROLLBACK");
Assert::parse("ROLLBACK AND CHAIN");
Assert::parse("ROLLBACK AND NO CHAIN");
Assert::parse("ROLLBACK RELEASE");
Assert::parse("ROLLBACK NO RELEASE");


// ROLLBACK [WORK] TO [SAVEPOINT] identifier
Assert::parse("ROLLBACK TO SAVEPOINT svp1");
Assert::parse("ROLLBACK WORK TO SAVEPOINT svp1", "ROLLBACK TO SAVEPOINT svp1");
Assert::parse("ROLLBACK TO svp1", "ROLLBACK TO SAVEPOINT svp1");


// SAVEPOINT identifier
Assert::parse("SAVEPOINT svp1");


// SET [GLOBAL | SESSION] TRANSACTION transaction_characteristic [, transaction_characteristic] ...
Assert::parse("SET GLOBAL TRANSACTION READ ONLY");
Assert::parse("SET SESSION TRANSACTION READ WRITE");
Assert::parse("SET TRANSACTION ISOLATION LEVEL REPEATABLE READ");
Assert::parse("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
Assert::parse("SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED");
Assert::parse("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
Assert::parse("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE, READ WRITE");


// START TRANSACTION [transaction_characteristic [, transaction_characteristic] ...]
// BEGIN [WORK]
Assert::parse("START TRANSACTION");
Assert::parse("START TRANSACTION WITH CONSISTENT SNAPSHOT");
Assert::parse("START TRANSACTION READ ONLY");
Assert::parse("START TRANSACTION READ WRITE");
Assert::parse("START TRANSACTION WITH CONSISTENT SNAPSHOT, READ WRITE");
Assert::parse("BEGIN", "START TRANSACTION");
Assert::parse("BEGIN WORK", "START TRANSACTION");


// UNLOCK TABLES
Assert::parse("UNLOCK TABLES");
