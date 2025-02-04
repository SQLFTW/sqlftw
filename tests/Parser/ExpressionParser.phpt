<?php

namespace SqlFtw\Parser;

use SqlFtw\Sql\Dml\Query\SelectCommand;
use SqlFtw\Tests\Assert;

require __DIR__ . '/../bootstrap.php';


// operators & recursion
Assert::parseSerialize("SELECT 1");
Assert::parseSerialize("SELECT 1 + 1 + 1");
Assert::parseSerialize("SELECT 1 - 1 - 1");
Assert::parseSerialize("SELECT 1 * 1 * 1");
Assert::parseSerialize("SELECT 1 / 1 / 1");
Assert::parseSerialize("SELECT 1 % 1 % 1");
Assert::parseSerialize("SELECT 1 IS NULL IS NULL");
Assert::parseSerialize("SELECT 1 IS NOT NULL IS NOT NULL");
Assert::parseSerialize("SELECT 1 IS NOT NULL IS NOT NULL IS NOT NULL");
Assert::parseSerialize("SELECT 1 IS TRUE IS TRUE");
Assert::parseSerialize("SELECT 1 IS NOT TRUE IS NOT TRUE");
Assert::parseSerialize("SELECT 1 IS FALSE IS FALSE");
Assert::parseSerialize("SELECT 1 IS NOT FALSE IS NOT FALSE");
Assert::parseSerialize("SELECT 1 OR 1 OR 1");
Assert::parseSerialize("SELECT 1 = 1 = 1");

Assert::parseSerialize("SELECT 1 + 1 * 1 > 0");
Assert::parseSerialize("SELECT @d1 := 1 * 1 > 0");
Assert::parseSerialize("SELECT d1 = 1 > 0");

Assert::parseSerialize("SELECT ( d2 = c2 % ASIN( d1 ) > i2 )");
Assert::parseSerialize("SELECT ( d2 = c2 % ASIN( d1 ) > i2 )");

// raw expressions
/** @var SelectCommand $command */
$command = Assert::validCommand("SELECT * FROM tbl1");
$expression = $command->columns[0];
Assert::same($expression->rawExpression, '*');

/** @var SelectCommand $command */
$command = Assert::validCommand("SELECT 1 + 1 as foo FROM tbl1");
$expression = $command->columns[0];
Assert::same($expression->rawExpression, '1 + 1');

/** @var SelectCommand $command */
$command = Assert::validCommand("SELECT 1  +  1 FROM tbl1");
$expression = $command->columns[0];
Assert::same($expression->rawExpression, '1  +  1');

/** @var SelectCommand $command */
$command = Assert::validCommand("SELECT 1+1 FROM tbl1");
$expression = $command->columns[0];
Assert::same($expression->rawExpression, '1+1');

/** @var SelectCommand $command */
$command = Assert::validCommand("SELECT /* before */ 1 /* inside */ + /* inside */ 1 /* after */ FROM tbl1");
$expression = $command->columns[0];
Assert::same($expression->rawExpression, '1 /* inside */ + /* inside */ 1');

/** @var SelectCommand $command */
$command = Assert::validCommand("SELECT /*before*//*before*/1/*inside*/+/*inside*/1/*after*//*after*/ FROM tbl1");
$expression = $command->columns[0];
Assert::same($expression->rawExpression, '1/*inside*/+/*inside*/1');

/** @var SelectCommand $command */
$command = Assert::validCommand("SELECT (x + y) - 2*z FROM tbl1");
$expression = $command->columns[0];
Assert::same($expression->rawExpression, '(x + y) - 2*z');

/** @var SelectCommand $command */
$command = Assert::validCommand("SELECT someFunc(0x1234EF) FROM tbl1");
$expression = $command->columns[0];
Assert::same($expression->rawExpression, 'someFunc(0x1234EF)');

/** @var SelectCommand $command */
$command = Assert::validCommand("SELECT someFunc(0x1234ef) FROM tbl1");
$expression = $command->columns[0];
Assert::same($expression->rawExpression, 'someFunc(0x1234ef)');
