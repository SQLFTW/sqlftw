<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// SET ...
Assert::parse("SET var1 = 1");
Assert::parse("SET var1 = 1, var2 = 2");
Assert::parse("SET @var1 = 1");
Assert::parse("SET @@basedir = 1");

Assert::parse("SET @@SESSION.basedir = 1");
Assert::parse("SET @@GLOBAL.basedir = 1");
Assert::parse("SET @@PERSIST.basedir = 1");
Assert::parse("SET @@PERSIST_ONLY.basedir = 1");

Assert::parse("SET @@session.basedir = 1", "SET @@SESSION.basedir = 1");
Assert::parse("SET @@global.basedir = 1", "SET @@GLOBAL.basedir = 1");
Assert::parse("SET @@persist.basedir = 1", "SET @@PERSIST.basedir = 1");
Assert::parse("SET @@persist_only.basedir = 1", "SET @@PERSIST_ONLY.basedir = 1");

Assert::parse("SET SESSION basedir = 1", "SET @@SESSION.basedir = 1");
Assert::parse("SET GLOBAL basedir = 1", "SET @@GLOBAL.basedir = 1");
Assert::parse("SET PERSIST basedir = 1", "SET @@PERSIST.basedir = 1");
Assert::parse("SET PERSIST_ONLY basedir = 1", "SET @@PERSIST_ONLY.basedir = 1");

Assert::parse("SET @@LOCAL.basedir = 1", "SET @@SESSION.basedir = 1");
Assert::parse("SET @@local.basedir = 1", "SET @@SESSION.basedir = 1");
Assert::parse("SET LOCAL basedir = 1", "SET @@SESSION.basedir = 1");
