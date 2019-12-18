<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// SET ...
Assert::parse("SET foo = 1");
Assert::parse("SET foo = 1, bar = 2");
Assert::parse("SET @foo = 1");
Assert::parse("SET @@basedir = 1", "SET SESSION basedir = 1");
Assert::parse("SET @@SESSION.basedir = 1", "SET SESSION basedir = 1");
Assert::parse("SET @@GLOBAL.basedir = 1", "SET GLOBAL basedir = 1");
Assert::parse("SET @@PERSIST.basedir = 1", "SET PERSIST basedir = 1");
Assert::parse("SET @@PERSIST_ONLY.basedir = 1", "SET PERSIST_ONLY basedir = 1");
Assert::parse("SET SESSION basedir = 1");
Assert::parse("SET GLOBAL basedir = 1");
Assert::parse("SET PERSIST basedir = 1");
Assert::parse("SET PERSIST_ONLY basedir = 1");
