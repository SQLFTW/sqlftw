<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// SET CHARSET
Assert::parse("SET CHARACTER SET 'utf8'");
Assert::parse("SET CHARSET DEFAULT", "SET CHARACTER SET DEFAULT");


// SET NAMES
Assert::parse("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'");
Assert::parse("SET NAMES DEFAULT");
