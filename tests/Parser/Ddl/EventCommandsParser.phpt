<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';

// ALTER [DEFINER = { user | CURRENT_USER }] EVENT event_name
Assert::parse("ALTER EVENT evt1");
Assert::parse("ALTER DEFINER usr1@host1 EVENT evt1");
Assert::parse("ALTER DEFINER CURRENT_USER EVENT evt1");

// [ON SCHEDULE schedule:]
//   {AT timestamp [+ INTERVAL interval] ... | EVERY interval}
//   [STARTS timestamp [+ INTERVAL interval] ...]
//   [ENDS timestamp [+ INTERVAL interval] ...]
Assert::parse("ALTER EVENT evt1 ON SCHEDULE AT '2001-02-03 04:05:06.000007'");
Assert::parse("ALTER EVENT evt1 ON SCHEDULE AT '2001-02-03 04:05:06.000007' + INTERVAL 6 HOUR");
Assert::parse("ALTER EVENT evt1 ON SCHEDULE EVERY 6 HOUR");
Assert::parse("ALTER EVENT evt1 ON SCHEDULE EVERY 6 HOUR STARTS '2001-02-03 04:05:06.000007'");
Assert::parse("ALTER EVENT evt1 ON SCHEDULE EVERY 6 HOUR STARTS '2001-02-03 04:05:06.000007' + INTERVAL 6 HOUR");
Assert::parse("ALTER EVENT evt1 ON SCHEDULE EVERY 6 HOUR ENDS '2001-02-03 04:05:06.000007'");
Assert::parse("ALTER EVENT evt1 ON SCHEDULE EVERY 6 HOUR ENDS '2001-02-03 04:05:06.000007' + INTERVAL 6 HOUR");
Assert::parse("ALTER EVENT evt1 ON SCHEDULE EVERY 6 HOUR STARTS '2001-02-03 04:05:06.000007' ENDS '2011-02-03 04:05:06.000007'");

// [ON COMPLETION [NOT] PRESERVE]
Assert::parse("ALTER EVENT evt1 ON COMPLETION PRESERVE");
Assert::parse("ALTER EVENT evt1 ON COMPLETION NOT PRESERVE");

// [RENAME TO new_event_name]
Assert::parse("ALTER EVENT evt1 RENAME TO evt2");

// [ENABLE | DISABLE | DISABLE ON SLAVE]
Assert::parse("ALTER EVENT evt1 ENABLE");
Assert::parse("ALTER EVENT evt1 DISABLE");
Assert::parse("ALTER EVENT evt1 DISABLE ON SLAVE");

// [COMMENT 'comment']
Assert::parse("ALTER EVENT evt1 COMMENT 'com1'");

// [DO event_body]
Assert::parse("ALTER EVENT evt1 DO stuff()");


// CREATE [DEFINER = { user | CURRENT_USER }] EVENT [IF NOT EXISTS] event_name ON SCHEDULE schedule
//   [ON COMPLETION [NOT] PRESERVE]
//   [ENABLE | DISABLE | DISABLE ON SLAVE]
//   [COMMENT 'comment']
//   DO event_body
Assert::parse("CREATE EVENT evt1 ON SCHEDULE EVERY 6 HOUR DO stuff()");
Assert::parse("CREATE DEFINER = usr1@host1 EVENT evt1 ON SCHEDULE EVERY 6 HOUR DO stuff()");
Assert::parse("CREATE DEFINER = CURRENT_USER EVENT evt1 ON SCHEDULE EVERY 6 HOUR DO stuff()");
Assert::parse(
    "CREATE DEFINER CURRENT_USER EVENT evt1 ON SCHEDULE EVERY 6 HOUR DO stuff()",
    "CREATE DEFINER = CURRENT_USER EVENT evt1 ON SCHEDULE EVERY 6 HOUR DO stuff()" // [=]
);
Assert::parse("CREATE EVENT IF NOT EXISTS evt1 ON SCHEDULE EVERY 6 HOUR DO stuff()");
Assert::parse("CREATE EVENT evt1 ON SCHEDULE EVERY 6 HOUR DO stuff()");
Assert::parse("CREATE EVENT evt1 ON SCHEDULE EVERY 6 HOUR ON COMPLETION PRESERVE DO stuff()");
Assert::parse("CREATE EVENT evt1 ON SCHEDULE EVERY 6 HOUR ON COMPLETION NOT PRESERVE DO stuff()");
Assert::parse("CREATE EVENT evt1 ON SCHEDULE EVERY 6 HOUR ENABLE DO stuff()");
Assert::parse("CREATE EVENT evt1 ON SCHEDULE EVERY 6 HOUR DISABLE DO stuff()");
Assert::parse("CREATE EVENT evt1 ON SCHEDULE EVERY 6 HOUR DISABLE ON SLAVE DO stuff()");
Assert::parse("CREATE EVENT evt1 ON SCHEDULE EVERY 6 HOUR COMMENT 'com1' DO stuff()");


// DROP EVENT [IF EXISTS] event_name
Assert::parse("DROP EVENT evt1");
Assert::parse("DROP EVENT IF EXISTS evt1");
