<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// DO expr [, expr] ...
Assert::parse("DO proc1");
Assert::parse("DO proc1(var1)");
Assert::parse("DO proc1(var1, var2)");
Assert::parse("DO proc1(), proc2()");
