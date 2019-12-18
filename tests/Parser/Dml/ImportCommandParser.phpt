<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// IMPORT TABLE FROM sdi_file [, sdi_file] ...
Assert::parse("IMPORT TABLE FROM 'foo'");
Assert::parse("IMPORT TABLE FROM 'foo', 'bar'");
