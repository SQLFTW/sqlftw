<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';

// ALTER {DATABASE | SCHEMA} [db_name] alter_specification ...
Assert::parse("ALTER DATABASE foo CHARACTER SET ascii");
Assert::parse("ALTER DATABASE foo CHARSET ascii", "ALTER DATABASE foo CHARACTER SET ascii"); // CHARSET -> CHARACTER SET
Assert::parse("ALTER DATABASE foo CHARACTER SET 'ascii'", "ALTER DATABASE foo CHARACTER SET ascii"); // '...' -> ...
Assert::parse("ALTER SCHEMA foo CHARACTER SET 'ascii'", "ALTER DATABASE foo CHARACTER SET ascii"); // SCHEMA -> DATABASE
Assert::parse("ALTER DATABASE foo CHARACTER SET = 'ascii'", "ALTER DATABASE foo CHARACTER SET ascii"); // [=]
Assert::parse("ALTER DATABASE foo CHARACTER SET ascii");
Assert::parse("ALTER DATABASE foo COLLATE ascii_general_ci");
Assert::parse("ALTER DATABASE foo CHARACTER SET ascii COLLATE ascii_general_ci");
Assert::parse(
    "ALTER DATABASE foo DEFAULT CHARACTER SET ascii DEFAULT COLLATE ascii_general_ci",  // [DEFAULT]
    "ALTER DATABASE foo CHARACTER SET ascii COLLATE ascii_general_ci"
);


// CREATE {DATABASE | SCHEMA} [IF NOT EXISTS] db_name [create_specification] ...
Assert::parse("CREATE DATABASE foo CHARACTER SET ascii");
Assert::parse("CREATE DATABASE foo CHARSET ascii", "CREATE DATABASE foo CHARACTER SET ascii"); // CHARSET -> CHARACTER SET
Assert::parse("CREATE DATABASE foo CHARACTER SET 'ascii'", "CREATE DATABASE foo CHARACTER SET ascii"); // '...' -> ...
Assert::parse("CREATE DATABASE IF NOT EXISTS foo CHARACTER SET ascii");
Assert::parse("CREATE SCHEMA foo CHARACTER SET ascii", "CREATE DATABASE foo CHARACTER SET ascii"); // SCHEMA -> DATABASE
Assert::parse("CREATE DATABASE foo CHARACTER SET = ascii", "CREATE DATABASE foo CHARACTER SET ascii"); // [=]
Assert::parse("CREATE DATABASE foo COLLATE ascii_general_ci");
Assert::parse("CREATE DATABASE foo CHARACTER SET ascii COLLATE ascii_general_ci");
Assert::parse(
    "CREATE DATABASE foo DEFAULT CHARACTER SET ascii DEFAULT COLLATE ascii_general_ci", // [DEFAULT]
    "CREATE DATABASE foo CHARACTER SET ascii COLLATE ascii_general_ci"
);


// DROP {DATABASE | SCHEMA} [IF EXISTS] db_name
Assert::parse("DROP DATABASE foo");
Assert::parse("DROP DATABASE IF EXISTS foo");
Assert::parse("DROP SCHEMA foo", "DROP DATABASE foo");
