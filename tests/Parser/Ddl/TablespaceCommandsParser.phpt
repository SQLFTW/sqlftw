<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// ALTER [UNDO] TABLESPACE tablespace_name
Assert::parse("ALTER TABLESPACE foo");
Assert::parse("ALTER UNDO TABLESPACE foo");

// [{ADD|DROP} DATAFILE 'file_name'] -- NDB only
Assert::parse("ALTER TABLESPACE foo ADD DATAFILE 'bar'");
Assert::parse("ALTER TABLESPACE foo DROP DATAFILE 'bar'");

// [INITIAL_SIZE [=] size] -- NDB only
Assert::parse("ALTER TABLESPACE foo INITIAL_SIZE 1234");

// [WAIT] -- NDB only
Assert::parse("ALTER TABLESPACE foo WAIT");

// [RENAME TO tablespace_name]
Assert::parse("ALTER TABLESPACE foo RENAME TO bar");

// [SET {ACTIVE|INACTIVE}] -- InnoDB only
Assert::parse("ALTER TABLESPACE foo SET ACTIVE");
Assert::parse("ALTER TABLESPACE foo SET INACTIVE");

// [ENCRYPTION [=] {'Y' | 'N'}] -- InnoDB only
Assert::parse("ALTER TABLESPACE foo ENCRYPTION 'Y'");
Assert::parse("ALTER TABLESPACE foo ENCRYPTION 'N'");

// [ENGINE [=] engine_name]
Assert::parse("ALTER TABLESPACE foo ENGINE InnoDB");


// CREATE [UNDO] TABLESPACE tablespace_name
Assert::parse("CREATE TABLESPACE foo");
Assert::parse("CREATE UNDO TABLESPACE foo");

// [ADD DATAFILE 'file_name']
Assert::parse("CREATE TABLESPACE foo ADD DATAFILE 'bar'");

// [FILE_BLOCK_SIZE = value] -- InnoDB only
Assert::parse("CREATE TABLESPACE foo FILE_BLOCK_SIZE = 1234");

// [ENCRYPTION [=] {'Y' | 'N'}] -- InnoDB only
Assert::parse("CREATE TABLESPACE foo ENCRYPTION 'Y'");
Assert::parse("CREATE TABLESPACE foo ENCRYPTION 'N'");

// USE LOGFILE GROUP logfile_group -- NDB only
Assert::parse("CREATE TABLESPACE foo USE LOGFILE GROUP bar");

// [EXTENT_SIZE [=] extent_size] -- NDB only
Assert::parse("CREATE TABLESPACE foo EXTENT_SIZE 1234");

// [INITIAL_SIZE [=] initial_size] -- NDB only
Assert::parse("CREATE TABLESPACE foo INITIAL_SIZE 1234");

// [AUTOEXTEND_SIZE [=] autoextend_size] -- NDB only
Assert::parse("CREATE TABLESPACE foo AUTOEXTEND_SIZE 1234");

// [MAX_SIZE [=] max_size] -- NDB only
Assert::parse("CREATE TABLESPACE foo MAX_SIZE 1234");

// [NODEGROUP [=] nodegroup_id] -- NDB only
Assert::parse("CREATE TABLESPACE foo NODEGROUP 123");

// [WAIT] -- NDB only
Assert::parse("CREATE TABLESPACE foo WAIT");

// [COMMENT [=] 'string'] -- NDB only
Assert::parse("CREATE TABLESPACE foo COMMENT 'bar'");

// [ENGINE [=] engine_name]
Assert::parse("CREATE TABLESPACE foo ENGINE InnoDB");


// DROP [UNDO] TABLESPACE tablespace_name [ENGINE [=] engine_name]
Assert::parse("DROP TABLESPACE foo");
Assert::parse("DROP UNDO TABLESPACE foo");
Assert::parse("DROP TABLESPACE foo ENGINE InnoDB");
