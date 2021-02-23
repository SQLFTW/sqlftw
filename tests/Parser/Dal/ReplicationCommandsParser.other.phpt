<?php declare(strict_types = 1);

// phpcs:disable SlevomatCodingStandard.Functions.RequireSingleLineCall

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// PURGE { BINARY | MASTER } LOGS
Assert::parse("PURGE BINARY LOGS TO 'foo.log'");
Assert::parse("PURGE BINARY LOGS BEFORE '2001-01-01 01:01:01.000000'");

Assert::parse(
    "PURGE MASTER LOGS TO 'foo.log'",
    "PURGE BINARY LOGS TO 'foo.log'"
);
Assert::parse(
    "PURGE MASTER LOGS BEFORE '2001-01-01 01:01:01.000000'",
    "PURGE BINARY LOGS BEFORE '2001-01-01 01:01:01.000000'"
);


// RESET MASTER
Assert::parse("RESET MASTER");
Assert::parse("RESET MASTER TO 123");


// START GROUP_REPLICATION
Assert::parse("START GROUP_REPLICATION");


// STOP GROUP_REPLICATION
Assert::parse("STOP GROUP_REPLICATION");
