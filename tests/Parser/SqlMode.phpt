<?php

// phpcs:disable SlevomatCodingStandard.Functions.RequireSingleLineCall

namespace SqlFtw\Parser;

use SqlFtw\Platform\Platform;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\SqlMode;
use SqlFtw\Tests\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::exception(static function (): void {
    $platform = Platform::get(Platform::MYSQL);
    SqlMode::fromString('FOO', $platform);
}, InvalidDefinitionException::class);

Assert::invalidCommand("SET @@session.sql_mode = 'ERROR_FOR_DIVISION_BY_ZERO,FOOBAR,IGNORE_SPACE';");
Assert::invalidCommand("SET @@sql_mode=',,,,FOOBAR,,,,,';");