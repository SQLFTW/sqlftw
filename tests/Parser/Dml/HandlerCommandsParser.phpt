<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// HANDLER tbl_name OPEN [[AS] alias]
Assert::parse("HANDLER foo OPEN");
Assert::parse("HANDLER foo OPEN AS bar");


// HANDLER tbl_name READ index_name { = | <= | >= | < | > } (value1, value2, ...) [WHERE where_condition] [LIMIT ...]
Assert::parse("HANDLER foo READ bar = (1)");
Assert::parse("HANDLER foo READ bar <= (1)");
Assert::parse("HANDLER foo READ bar >= (1)");
Assert::parse("HANDLER foo READ bar < (1)");
Assert::parse("HANDLER foo READ bar > (1)");
Assert::parse("HANDLER foo READ bar = (1, 2, 3)");
Assert::parse("HANDLER foo READ bar = (1) WHERE baz = 1 AND baz != 1");
Assert::parse("HANDLER foo READ bar = (1) LIMIT 1 OFFSET 10");


// HANDLER tbl_name READ index_name { FIRST | NEXT | PREV | LAST } [WHERE where_condition] [LIMIT ...]
Assert::parse("HANDLER foo READ bar FIRST");
Assert::parse("HANDLER foo READ bar NEXT");
Assert::parse("HANDLER foo READ bar PREV");
Assert::parse("HANDLER foo READ bar LAST");
Assert::parse("HANDLER foo READ bar FIRST WHERE baz = 1 AND baz != 1");
Assert::parse("HANDLER foo READ bar FIRST LIMIT 1 OFFSET 10");


// HANDLER tbl_name READ { FIRST | NEXT } [WHERE where_condition] [LIMIT ...]
Assert::parse("HANDLER foo READ FIRST");
Assert::parse("HANDLER foo READ NEXT");
Assert::parse("HANDLER foo READ FIRST WHERE baz = 1 AND baz != 1");
Assert::parse("HANDLER foo READ FIRST LIMIT 1 OFFSET 10");


// HANDLER tbl_name CLOSE
Assert::parse("HANDLER foo CLOSE");
