<?php

// phpcs:disable SlevomatCodingStandard.Functions.RequireSingleLineCall

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::validCommands("
DELIMITER ;;
SELECT * FROM tbl1;;
DELIMITER ;
");

Assert::parseSerializeMany("DELIMITER ;;
SELECT * FROM tbl1;;
DELIMITER ;");

Assert::parseSerializeMany("SELECT 1;
SELECT 2;
SELECT 3;");

// parse without delimiter
Assert::parseSerialize("CREATE PROCEDURE proc1() BEGIN SELECT 1; END");
Assert::parseSerialize("CREATE PROCEDURE proc1() BEGIN SELECT 1; SELECT 2; END");
Assert::parseSerialize("CREATE PROCEDURE proc1() BEGIN SELECT 1; SELECT 2; SELECT 3; END");
