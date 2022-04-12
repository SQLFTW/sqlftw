<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// ALTER LOGFILE GROUP logfile_group ADD UNDOFILE 'file_name' [INITIAL_SIZE [=] size] [WAIT] ENGINE [=] engine_name
Assert::parse("ALTER LOGFILE GROUP grp1 ADD UNDOFILE 'file.log' ENGINE = eng1");
Assert::parse("ALTER LOGFILE GROUP grp1 ADD UNDOFILE 'file.log' ENGINE eng1", "ALTER LOGFILE GROUP grp1 ADD UNDOFILE 'file.log' ENGINE = eng1"); // [=]
Assert::parse("ALTER LOGFILE GROUP grp1 ADD UNDOFILE 'file.log' INITIAL_SIZE = 123 ENGINE = eng1");
Assert::parse("ALTER LOGFILE GROUP grp1 ADD UNDOFILE 'file.log' WAIT ENGINE = eng1");


// CREATE LOGFILE GROUP logfile_group ADD UNDOFILE 'undo_file' ... ENGINE [=] engine_name
Assert::parse("CREATE LOGFILE GROUP grp1 ADD UNDOFILE 'file.log' ENGINE = eng1");

// [INITIAL_SIZE [=] initial_size]
Assert::parse("CREATE LOGFILE GROUP grp1 ADD UNDOFILE 'file.log' INITIAL_SIZE = 123 ENGINE = eng1");

// [UNDO_BUFFER_SIZE [=] undo_buffer_size]
Assert::parse("CREATE LOGFILE GROUP grp1 ADD UNDOFILE 'file.log' UNDO_BUFFER_SIZE = 123 ENGINE = eng1");

// [REDO_BUFFER_SIZE [=] redo_buffer_size]
Assert::parse("CREATE LOGFILE GROUP grp1 ADD UNDOFILE 'file.log' REDO_BUFFER_SIZE = 123 ENGINE = eng1");

// [NODEGROUP [=] nodegroup_id]
Assert::parse("CREATE LOGFILE GROUP grp1 ADD UNDOFILE 'file.log' NODEGROUP = 123 ENGINE = eng1");

// [WAIT]
Assert::parse("CREATE LOGFILE GROUP grp1 ADD UNDOFILE 'file.log' WAIT ENGINE = eng1");

// [COMMENT [=] comment_text]
Assert::parse("CREATE LOGFILE GROUP grp1 ADD UNDOFILE 'file.log' COMMENT = 'com1' ENGINE = eng1");


// DROP LOGFILE GROUP logfile_group ENGINE [=] engine_name
Assert::parse("DROP LOGFILE GROUP grp1 ENGINE = eng1");
