<?php

// phpcs:disable SlevomatCodingStandard.Functions.RequireSingleLineCall

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::parseSerialize("CREATE TABLE occurrence (`empty` TINYINT(1) NOT NULL DEFAULT 0)");
Assert::parseSerialize("CREATE TABLE occurrence (`EMPTY` TINYINT(1) NOT NULL DEFAULT 0)");
