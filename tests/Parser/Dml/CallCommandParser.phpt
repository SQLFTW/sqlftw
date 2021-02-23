<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// CALL sp_name[([parameter[, ...]])]
Assert::parse("CALL foo");
Assert::parse("CALL foo(bar)");
Assert::parse("CALL foo(bar, baz)");
