<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// INSTALL PLUGIN
Assert::parse("INSTALL PLUGIN foo SONAME 'library.so'");

// UNINSTALL PLUGIN
Assert::parse("UNINSTALL PLUGIN foo");
