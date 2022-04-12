<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// RESET PERSIST [[IF EXISTS] system_var_name]
Assert::parse("RESET PERSIST");
Assert::parse("RESET PERSIST var");
Assert::parse("RESET PERSIST IF EXISTS var");
