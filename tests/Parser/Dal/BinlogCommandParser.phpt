<?php

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// BINLOG
Assert::parseSerialize("BINLOG 'file'");
