<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// SET ...
Assert::parseSerialize("SET var1 = 1");
Assert::parseSerialize("SET var1 = 1, var2 = 2");
Assert::parseSerialize("SET @var1 = 1");
Assert::parseSerialize("SET @@basedir = 1");

Assert::parseSerialize("SET @@SESSION.basedir = 1");
Assert::parseSerialize("SET @@GLOBAL.basedir = 1");
Assert::parseSerialize("SET @@PERSIST.basedir = 1");
Assert::parseSerialize("SET @@PERSIST_ONLY.basedir = 1");

Assert::parseSerialize("SET @@session.basedir = 1", "SET @@SESSION.basedir = 1");
Assert::parseSerialize("SET @@global.basedir = 1", "SET @@GLOBAL.basedir = 1");
Assert::parseSerialize("SET @@persist.basedir = 1", "SET @@PERSIST.basedir = 1");
Assert::parseSerialize("SET @@persist_only.basedir = 1", "SET @@PERSIST_ONLY.basedir = 1");

Assert::parseSerialize("SET SESSION basedir = 1", "SET @@SESSION.basedir = 1");
Assert::parseSerialize("SET GLOBAL basedir = 1", "SET @@GLOBAL.basedir = 1");
Assert::parseSerialize("SET PERSIST basedir = 1", "SET @@PERSIST.basedir = 1");
Assert::parseSerialize("SET PERSIST_ONLY basedir = 1", "SET @@PERSIST_ONLY.basedir = 1");

Assert::parseSerialize("SET @@LOCAL.basedir = 1", "SET @@SESSION.basedir = 1");
Assert::parseSerialize("SET @@local.basedir = 1", "SET @@SESSION.basedir = 1");
Assert::parseSerialize("SET LOCAL basedir = 1", "SET @@SESSION.basedir = 1");
