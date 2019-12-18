<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// CREATE FUNCTION SONAME
Assert::parse("CREATE AGGREGATE FUNCTION function_name RETURNS REAL SONAME 'shared_library_name'");
Assert::parse(
    "CREATE FUNCTION function_name RETURNS STRING SONAME shared_library_name",
    "CREATE FUNCTION function_name RETURNS STRING SONAME 'shared_library_name'"
);
