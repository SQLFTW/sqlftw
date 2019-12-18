<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// ALTER SERVER server_name OPTIONS (option [, option] ...)
Assert::parse("ALTER SERVER foo OPTIONS (HOST 'foo.example.com')");
Assert::parse("ALTER SERVER foo OPTIONS (DATABASE 'foo')");
Assert::parse("ALTER SERVER foo OPTIONS (USER 'foo')");
Assert::parse("ALTER SERVER foo OPTIONS (PASSWORD 'foo')");
Assert::parse("ALTER SERVER foo OPTIONS (SOCKET 'foo')");
Assert::parse("ALTER SERVER foo OPTIONS (OWNER 'foo')");
Assert::parse("ALTER SERVER foo OPTIONS (PORT 12345)");
Assert::parse("ALTER SERVER foo OPTIONS (HOST 'foo.example.com', PORT 12345)");


// CREATE SERVER server_name FOREIGN DATA WRAPPER wrapper_name OPTIONS (option [, option] ...)
Assert::parse("CREATE SERVER foo FOREIGN DATA WRAPPER 'bar' OPTIONS (HOST 'foo.example.com')");
Assert::parse("CREATE SERVER foo FOREIGN DATA WRAPPER 'bar' OPTIONS (DATABASE 'foo')");
Assert::parse("CREATE SERVER foo FOREIGN DATA WRAPPER 'bar' OPTIONS (USER 'foo')");
Assert::parse("CREATE SERVER foo FOREIGN DATA WRAPPER 'bar' OPTIONS (PASSWORD 'foo')");
Assert::parse("CREATE SERVER foo FOREIGN DATA WRAPPER 'bar' OPTIONS (SOCKET 'foo')");
Assert::parse("CREATE SERVER foo FOREIGN DATA WRAPPER 'bar' OPTIONS (OWNER 'foo')");
Assert::parse("CREATE SERVER foo FOREIGN DATA WRAPPER 'bar' OPTIONS (PORT 12345)");
Assert::parse("CREATE SERVER foo FOREIGN DATA WRAPPER 'bar' OPTIONS (HOST 'foo.example.com', PORT 12345)");


// DROP SERVER [ IF EXISTS ] server_name
Assert::parse("DROP SERVER foo");
Assert::parse("DROP SERVER IF EXISTS foo");
