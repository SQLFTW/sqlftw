<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// INSTALL COMPONENT
Assert::parse("INSTALL COMPONENT foo, bar");


// UNINSTALL COMPONENT
Assert::parse("UNINSTALL COMPONENT foo, bar");
