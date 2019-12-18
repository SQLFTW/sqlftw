<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// XA {START|BEGIN} xid [JOIN|RESUME]
Assert::parse("XA START 'foo'");
Assert::parse("XA START 'foo', 'bar'");
Assert::parse("XA START 'foo', 'bar', 1");
Assert::parse("XA BEGIN 'foo'", "XA START 'foo'");
Assert::parse("XA START 'foo' JOIN");
Assert::parse("XA START 'foo' RESUME");


// XA END xid [SUSPEND [FOR MIGRATE]]
Assert::parse("XA END 'foo'");
Assert::parse("XA END 'foo' SUSPEND");
Assert::parse("XA END 'foo' SUSPEND FOR MIGRATE");


// XA PREPARE xid
Assert::parse("XA PREPARE 'foo'");


// XA COMMIT xid [ONE PHASE]
Assert::parse("XA COMMIT 'foo'");
Assert::parse("XA COMMIT 'foo' ONE PHASE");


// XA ROLLBACK xid
Assert::parse("XA ROLLBACK 'foo'");


// XA RECOVER [CONVERT XID]
Assert::parse("XA RECOVER");
Assert::parse("XA RECOVER CONVERT XID");
