<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// ALTER LOGFILE GROUP logfile_group ADD UNDOFILE 'file_name' [INITIAL_SIZE [=] size] [WAIT] ENGINE [=] engine_name
Assert::parse("ALTER LOGFILE GROUP foo ADD UNDOFILE 'foo.log' ENGINE = bar");
Assert::parse("ALTER LOGFILE GROUP foo ADD UNDOFILE 'foo.log' ENGINE bar", "ALTER LOGFILE GROUP foo ADD UNDOFILE 'foo.log' ENGINE = bar");
Assert::parse("ALTER LOGFILE GROUP foo ADD UNDOFILE 'foo.log' INITIAL_SIZE = 123 ENGINE = bar");
Assert::parse("ALTER LOGFILE GROUP foo ADD UNDOFILE 'foo.log' WAIT ENGINE = bar");


// CREATE LOGFILE GROUP logfile_group ADD UNDOFILE 'undo_file' ... ENGINE [=] engine_name
Assert::parse("CREATE LOGFILE GROUP foo ADD UNDOFILE 'foo.log' ENGINE = bar");

// [INITIAL_SIZE [=] initial_size]
Assert::parse("CREATE LOGFILE GROUP foo ADD UNDOFILE 'foo.log' INITIAL_SIZE = 123 ENGINE = bar");

// [UNDO_BUFFER_SIZE [=] undo_buffer_size]
Assert::parse("CREATE LOGFILE GROUP foo ADD UNDOFILE 'foo.log' UNDO_BUFFER_SIZE = 123 ENGINE = bar");

// [REDO_BUFFER_SIZE [=] redo_buffer_size]
Assert::parse("CREATE LOGFILE GROUP foo ADD UNDOFILE 'foo.log' REDO_BUFFER_SIZE = 123 ENGINE = bar");

// [NODEGROUP [=] nodegroup_id]
Assert::parse("CREATE LOGFILE GROUP foo ADD UNDOFILE 'foo.log' NODEGROUP = 123 ENGINE = bar");

// [WAIT]
Assert::parse("CREATE LOGFILE GROUP foo ADD UNDOFILE 'foo.log' WAIT ENGINE = bar");

// [COMMENT [=] comment_text]
Assert::parse("CREATE LOGFILE GROUP foo ADD UNDOFILE 'foo.log' COMMENT = 'foo' ENGINE = bar");


// DROP LOGFILE GROUP logfile_group ENGINE [=] engine_name
Assert::parse("DROP LOGFILE GROUP foo ENGINE = bar");
