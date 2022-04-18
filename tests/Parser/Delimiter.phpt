<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::validCommands("
DELIMITER ;;
SELECT * FROM tbl1;;
DELIMITER ;
");