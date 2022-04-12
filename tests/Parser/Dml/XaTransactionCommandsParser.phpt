<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// XA {START|BEGIN} xid [JOIN|RESUME]
Assert::parse("XA START 'tr1'");
Assert::parse("XA START 'tr1', 'tr2'");
Assert::parse("XA START 'tr1', 'tr2', 1");
Assert::parse("XA BEGIN 'tr1'", "XA START 'tr1'"); // BEGIN -> START
Assert::parse("XA START 'tr1' JOIN");
Assert::parse("XA START 'tr1' RESUME");


// XA END xid [SUSPEND [FOR MIGRATE]]
Assert::parse("XA END 'tr1'");
Assert::parse("XA END 'tr1' SUSPEND");
Assert::parse("XA END 'tr1' SUSPEND FOR MIGRATE");


// XA PREPARE xid
Assert::parse("XA PREPARE 'tr1'");


// XA COMMIT xid [ONE PHASE]
Assert::parse("XA COMMIT 'tr1'");
Assert::parse("XA COMMIT 'tr1' ONE PHASE");


// XA ROLLBACK xid
Assert::parse("XA ROLLBACK 'tr1'");


// XA RECOVER [CONVERT XID]
Assert::parse("XA RECOVER");
Assert::parse("XA RECOVER CONVERT XID");
