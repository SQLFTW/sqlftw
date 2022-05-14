<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';

// ALTER {DATABASE | SCHEMA} [db_name] alter_specification ...
Assert::parse("ALTER SCHEMA db1 CHARACTER SET ascii");
Assert::parse("ALTER SCHEMA db1 CHARSET ascii", "ALTER SCHEMA db1 CHARACTER SET ascii"); // CHARSET -> CHARACTER SET
Assert::parse("ALTER SCHEMA db1 CHARACTER SET 'ascii'", "ALTER SCHEMA db1 CHARACTER SET ascii"); // '...' -> ...
Assert::parse("ALTER DATABASE db1 CHARACTER SET 'ascii'", "ALTER SCHEMA db1 CHARACTER SET ascii"); // DATABASE -> SCHEMA
Assert::parse("ALTER SCHEMA db1 CHARACTER SET = 'ascii'", "ALTER SCHEMA db1 CHARACTER SET ascii"); // [=]
Assert::parse("ALTER SCHEMA db1 CHARACTER SET ascii");
Assert::parse("ALTER SCHEMA db1 COLLATE ascii_general_ci");
Assert::parse("ALTER SCHEMA db1 CHARACTER SET ascii COLLATE ascii_general_ci");
Assert::parse(
    "ALTER SCHEMA db1 DEFAULT CHARACTER SET ascii DEFAULT COLLATE ascii_general_ci",  // [DEFAULT]
    "ALTER SCHEMA db1 CHARACTER SET ascii COLLATE ascii_general_ci"
);


// CREATE {DATABASE | SCHEMA} [IF NOT EXISTS] db_name [create_specification] ...
Assert::parse("CREATE SCHEMA db1 CHARACTER SET ascii");
Assert::parse("CREATE SCHEMA db1 CHARSET ascii", "CREATE SCHEMA db1 CHARACTER SET ascii"); // CHARSET -> CHARACTER SET
Assert::parse("CREATE SCHEMA db1 CHARACTER SET 'ascii'", "CREATE SCHEMA db1 CHARACTER SET ascii"); // '...' -> ...
Assert::parse("CREATE SCHEMA IF NOT EXISTS db1 CHARACTER SET ascii");
Assert::parse("CREATE DATABASE db1 CHARACTER SET ascii", "CREATE SCHEMA db1 CHARACTER SET ascii"); // DATABASE -> SCHEMA
Assert::parse("CREATE SCHEMA db1 CHARACTER SET = ascii", "CREATE SCHEMA db1 CHARACTER SET ascii"); // [=]
Assert::parse("CREATE SCHEMA db1 COLLATE ascii_general_ci");
Assert::parse("CREATE SCHEMA db1 CHARACTER SET ascii COLLATE ascii_general_ci");
Assert::parse(
    "CREATE SCHEMA db1 DEFAULT CHARACTER SET ascii DEFAULT COLLATE ascii_general_ci", // [DEFAULT]
    "CREATE SCHEMA db1 CHARACTER SET ascii COLLATE ascii_general_ci"
);


// DROP {DATABASE | SCHEMA} [IF EXISTS] db_name
Assert::parse("DROP SCHEMA db1");
Assert::parse("DROP SCHEMA IF EXISTS db1");
Assert::parse("DROP DATABASE db1", "DROP SCHEMA db1"); // DATABASE -> SCHEMA
