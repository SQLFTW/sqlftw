<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// ALTER [UNDO] TABLESPACE tablespace_name
Assert::parse("ALTER TABLESPACE tbs1");
Assert::parse("ALTER UNDO TABLESPACE tbs1");

// [{ADD|DROP} DATAFILE 'file_name'] -- NDB only
Assert::parse("ALTER TABLESPACE tbs1 ADD DATAFILE 'file1'");
Assert::parse("ALTER TABLESPACE tbs1 DROP DATAFILE 'file1'");

// [INITIAL_SIZE [=] size] -- NDB only
Assert::parse("ALTER TABLESPACE tbs1 INITIAL_SIZE 1234");

// [WAIT] -- NDB only
Assert::parse("ALTER TABLESPACE tbs1 WAIT");

// [RENAME TO tablespace_name]
Assert::parse("ALTER TABLESPACE tbs1 RENAME TO tbs2");

// [SET {ACTIVE|INACTIVE}] -- InnoDB only
Assert::parse("ALTER TABLESPACE tbs1 SET ACTIVE");
Assert::parse("ALTER TABLESPACE tbs1 SET INACTIVE");

// [ENCRYPTION [=] {'Y' | 'N'}] -- InnoDB only
Assert::parse("ALTER TABLESPACE tbs1 ENCRYPTION 'Y'");
Assert::parse("ALTER TABLESPACE tbs1 ENCRYPTION 'N'");

// [ENGINE [=] engine_name]
Assert::parse("ALTER TABLESPACE tbs1 ENGINE InnoDB");


// CREATE [UNDO] TABLESPACE tablespace_name
Assert::parse("CREATE TABLESPACE tbs1");
Assert::parse("CREATE UNDO TABLESPACE tbs1");

// [ADD DATAFILE 'file_name']
Assert::parse("CREATE TABLESPACE tbs1 ADD DATAFILE 'file1'");

// [FILE_BLOCK_SIZE = value] -- InnoDB only
Assert::parse("CREATE TABLESPACE tbs1 FILE_BLOCK_SIZE = 1234");

// [ENCRYPTION [=] {'Y' | 'N'}] -- InnoDB only
Assert::parse("CREATE TABLESPACE tbs1 ENCRYPTION 'Y'");
Assert::parse("CREATE TABLESPACE tbs1 ENCRYPTION 'N'");

// USE LOGFILE GROUP logfile_group -- NDB only
Assert::parse("CREATE TABLESPACE tbs1 USE LOGFILE GROUP grp1");

// [EXTENT_SIZE [=] extent_size] -- NDB only
Assert::parse("CREATE TABLESPACE tbs1 EXTENT_SIZE 1234");

// [INITIAL_SIZE [=] initial_size] -- NDB only
Assert::parse("CREATE TABLESPACE tbs1 INITIAL_SIZE 1234");

// [AUTOEXTEND_SIZE [=] autoextend_size] -- NDB only
Assert::parse("CREATE TABLESPACE tbs1 AUTOEXTEND_SIZE 1234");

// [MAX_SIZE [=] max_size] -- NDB only
Assert::parse("CREATE TABLESPACE tbs1 MAX_SIZE 1234");

// [NODEGROUP [=] nodegroup_id] -- NDB only
Assert::parse("CREATE TABLESPACE tbs1 NODEGROUP 123");

// [WAIT] -- NDB only
Assert::parse("CREATE TABLESPACE tbs1 WAIT");

// [COMMENT [=] 'string'] -- NDB only
Assert::parse("CREATE TABLESPACE tbs1 COMMENT 'com1'");

// [ENGINE [=] engine_name]
Assert::parse("CREATE TABLESPACE tbs1 ENGINE InnoDB");


// DROP [UNDO] TABLESPACE tablespace_name [ENGINE [=] engine_name]
Assert::parse("DROP TABLESPACE tbs1");
Assert::parse("DROP UNDO TABLESPACE tbs1");
Assert::parse("DROP TABLESPACE tbs1 ENGINE InnoDB");
