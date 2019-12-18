<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// RESET reset_option [, reset_option] ...
Assert::parse("RESET MASTER");
Assert::parse("RESET SLAVE");
Assert::parse("RESET QUERY CACHE");
Assert::parse("RESET MASTER, SLAVE, QUERY CACHE");
