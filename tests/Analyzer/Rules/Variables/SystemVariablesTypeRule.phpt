<?php

namespace SqlFtw\Tests\Sql;

use SqlFtw\Platform\Platform;
use SqlFtw\Tests\CurrenVersion;
use SqlFtw\Tests\ParserSuiteFactory;

require __DIR__ . '/../../../bootstrap.php';

$suite = ParserSuiteFactory::fromPlatform(Platform::MYSQL, CurrenVersion::MYSQL);

$result = $suite->analyzer->analyzeSingle("SET sql_quote_show_create=_latin1 x'5452DC45';");
rd($result, 7);
