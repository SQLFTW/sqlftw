<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// KILL
Assert::parse("KILL 17");
Assert::parse("KILL CONNECTION 17", "KILL 17");
Assert::parse("KILL QUERY 17", "KILL 17");
