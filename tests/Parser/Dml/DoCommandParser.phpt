<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// DO expr [, expr] ...
Assert::parse("DO foo");
Assert::parse("DO foo(bar)");
Assert::parse("DO foo(bar, baz)");
Assert::parse("DO foo(), bar()");
