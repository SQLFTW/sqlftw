<?php

// phpcs:disable SlevomatCodingStandard.Functions.RequireSingleLineCall

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../bootstrap.php';

// Collation
Assert::parseSerialize("CREATE TABLE t2 (i INTEGER, a VARCHAR(10) COLLATE utf8_phone_ci) COLLATE utf8_phone_ci");
Assert::parseSerialize("SET @@GLOBAL.collation_server = Latin7_GeneRal_cS");

// BuiltInFunction
Assert::parseSerialize("SELECT CAST(98.6 AS DECIMAL(2, 0))");
Assert::parseSerialize("CREATE TABLE t1 (
    type_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
