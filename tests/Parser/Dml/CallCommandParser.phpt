<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// CALL sp_name[([parameter[, ...]])]
Assert::parse("CALL proc1");
Assert::parse("CALL proc1(var1)");
Assert::parse("CALL proc1(var1, var2)");
