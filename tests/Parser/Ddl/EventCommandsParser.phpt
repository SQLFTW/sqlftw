<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';

// ALTER [DEFINER = { user | CURRENT_USER }] EVENT event_name
Assert::parse("ALTER EVENT foo");
Assert::parse("ALTER DEFINER 'admin'@'localhost' EVENT foo");
Assert::parse("ALTER DEFINER CURRENT_USER EVENT foo");

// [ON SCHEDULE schedule:]
//   {AT timestamp [+ INTERVAL interval] ... | EVERY interval}
//   [STARTS timestamp [+ INTERVAL interval] ...]
//   [ENDS timestamp [+ INTERVAL interval] ...]
Assert::parse("ALTER EVENT foo ON SCHEDULE AT '2001-02-03 04:05:06.000007'");
Assert::parse("ALTER EVENT foo ON SCHEDULE AT '2001-02-03 04:05:06.000007' + INTERVAL 6 HOUR");
Assert::parse("ALTER EVENT foo ON SCHEDULE EVERY 6 HOUR");
Assert::parse("ALTER EVENT foo ON SCHEDULE EVERY 6 HOUR STARTS '2001-02-03 04:05:06.000007'");
Assert::parse("ALTER EVENT foo ON SCHEDULE EVERY 6 HOUR STARTS '2001-02-03 04:05:06.000007' + INTERVAL 6 HOUR");
Assert::parse("ALTER EVENT foo ON SCHEDULE EVERY 6 HOUR ENDS '2001-02-03 04:05:06.000007'");
Assert::parse("ALTER EVENT foo ON SCHEDULE EVERY 6 HOUR ENDS '2001-02-03 04:05:06.000007' + INTERVAL 6 HOUR");
Assert::parse("ALTER EVENT foo ON SCHEDULE EVERY 6 HOUR STARTS '2001-02-03 04:05:06.000007' ENDS '2011-02-03 04:05:06.000007'");

// [ON COMPLETION [NOT] PRESERVE]
Assert::parse("ALTER EVENT foo ON COMPLETION PRESERVE");
Assert::parse("ALTER EVENT foo ON COMPLETION NOT PRESERVE");

// [RENAME TO new_event_name]
Assert::parse("ALTER EVENT foo RENAME TO bar");

// [ENABLE | DISABLE | DISABLE ON SLAVE]
Assert::parse("ALTER EVENT foo ENABLE");
Assert::parse("ALTER EVENT foo DISABLE");
Assert::parse("ALTER EVENT foo DISABLE ON SLAVE");

// [COMMENT 'comment']
Assert::parse("ALTER EVENT foo COMMENT 'bar'");

// [DO event_body]
Assert::parse("ALTER EVENT foo DO stuff()");


// CREATE [DEFINER = { user | CURRENT_USER }] EVENT [IF NOT EXISTS] event_name ON SCHEDULE schedule
//   [ON COMPLETION [NOT] PRESERVE]
//   [ENABLE | DISABLE | DISABLE ON SLAVE]
//   [COMMENT 'comment']
//   DO event_body
Assert::parse("CREATE EVENT foo ON SCHEDULE EVERY 6 HOUR DO stuff()");
Assert::parse("CREATE DEFINER = 'admin'@'localhost' EVENT foo ON SCHEDULE EVERY 6 HOUR DO stuff()");
Assert::parse("CREATE DEFINER = CURRENT_USER EVENT foo ON SCHEDULE EVERY 6 HOUR DO stuff()");
Assert::parse(
    "CREATE DEFINER CURRENT_USER EVENT foo ON SCHEDULE EVERY 6 HOUR DO stuff()",
    "CREATE DEFINER = CURRENT_USER EVENT foo ON SCHEDULE EVERY 6 HOUR DO stuff()"
);
Assert::parse("CREATE EVENT IF NOT EXISTS foo ON SCHEDULE EVERY 6 HOUR DO stuff()");
Assert::parse("CREATE EVENT foo ON SCHEDULE EVERY 6 HOUR DO stuff()");
Assert::parse("CREATE EVENT foo ON SCHEDULE EVERY 6 HOUR ON COMPLETION PRESERVE DO stuff()");
Assert::parse("CREATE EVENT foo ON SCHEDULE EVERY 6 HOUR ON COMPLETION NOT PRESERVE DO stuff()");
Assert::parse("CREATE EVENT foo ON SCHEDULE EVERY 6 HOUR ENABLE DO stuff()");
Assert::parse("CREATE EVENT foo ON SCHEDULE EVERY 6 HOUR DISABLE DO stuff()");
Assert::parse("CREATE EVENT foo ON SCHEDULE EVERY 6 HOUR DISABLE ON SLAVE DO stuff()");
Assert::parse("CREATE EVENT foo ON SCHEDULE EVERY 6 HOUR COMMENT 'bar' DO stuff()");


// DROP EVENT [IF EXISTS] event_name
Assert::parse("DROP EVENT foo");
Assert::parse("DROP EVENT IF EXISTS foo");
