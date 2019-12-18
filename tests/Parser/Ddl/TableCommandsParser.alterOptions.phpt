<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// ALGORITHM
Assert::parse("ALTER TABLE tbl1 ALGORITHM INSTANT");
Assert::parse("ALTER TABLE tbl1 ALGORITHM INPLACE");
Assert::parse("ALTER TABLE tbl1 ALGORITHM COPY");
Assert::parse("ALTER TABLE tbl1 ALGORITHM DEFAULT");

// LOCK
Assert::parse("ALTER TABLE tbl1 LOCK DEFAULT");
Assert::parse("ALTER TABLE tbl1 LOCK NONE");
Assert::parse("ALTER TABLE tbl1 LOCK SHARED");
Assert::parse("ALTER TABLE tbl1 LOCK EXCLUSIVE");

// FORCE
Assert::parse("ALTER TABLE tbl1 FORCE");

// VALIDATION
Assert::parse("ALTER TABLE tbl1 WITH VALIDATION");
Assert::parse("ALTER TABLE tbl1 WITHOUT VALIDATION");
