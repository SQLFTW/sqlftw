<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Tests\Assert;

require __DIR__ . '/../../bootstrap.php';


// {DEALLOCATE | DROP} PREPARE stmt_name
Assert::parse("DEALLOCATE PREPARE foo");
Assert::parse("DROP PREPARE foo", "DEALLOCATE PREPARE foo");


// EXECUTE stmt_name [USING @var_name [, @var_name] ...]
Assert::parse("EXECUTE foo");
Assert::parse("EXECUTE foo USING @bar");
Assert::parse("EXECUTE foo USING @bar, @baz");


// PREPARE stmt_name FROM preparable_stmt
Assert::parse("PREPARE foo FROM 'SELECT 1'");
Assert::parse("PREPARE foo FROM @bar");
