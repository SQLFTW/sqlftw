<?php

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// USE ...
Assert::parseSerialize("USE db1");
