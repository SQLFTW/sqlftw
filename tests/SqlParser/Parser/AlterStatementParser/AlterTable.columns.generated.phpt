<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\Column\GeneratedColumnType;
use Tester\Assert;

require '../../../bootstrap.php';

// phpcs:disable
\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse(
    'ALTER TABLE `test`
        ADD `col_0` int,
        ADD `col_1` int AS (YEAR(`col_0`)),
        ADD `col_2` int AS (YEAR(`col_0`)) VIRTUAL,
        ADD `col_3` int AS (YEAR(`col_0`)) STORED,
        ADD `col_4` int GENERATED ALWAYS AS (YEAR(`col_0`)),
        ADD `col_5` int GENERATED ALWAYS AS (YEAR(`col_0`)) VIRTUAL,
        ADD `col_6` int GENERATED ALWAYS AS (YEAR(`col_0`)) STORED
    '
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];

/** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] $columns */
$columns = array_values($command->getColumnList()->getAddedColumns());
Assert::count(7, $columns);

// generated
Assert::false($columns[0]->isGenerated());
Assert::true($columns[1]->isGenerated());
Assert::true($columns[2]->isGenerated());
Assert::true($columns[3]->isGenerated());
Assert::true($columns[4]->isGenerated());
Assert::true($columns[5]->isGenerated());
Assert::true($columns[6]->isGenerated());

// type
Assert::same(GeneratedColumnType::VIRTUAL, $columns[1]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::VIRTUAL, $columns[2]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::STORED, $columns[3]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::VIRTUAL, $columns[4]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::VIRTUAL, $columns[5]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::STORED, $columns[6]->getGeneratedColumnType()->getValue());

// expression
Assert::same('(YEAR(`col_0`))', $columns[1]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[2]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[3]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[4]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[5]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[6]->getExpression());


$commands = $parser->parse(
    'ALTER TABLE `test`
        CHANGE `col_0` `col_0` int,
        CHANGE `col_1` `col_1` int AS (YEAR(`col_0`)),
        CHANGE `col_2` `col_2` int AS (YEAR(`col_0`)) VIRTUAL,
        CHANGE `col_3` `col_3` int AS (YEAR(`col_0`)) STORED,
        CHANGE `col_4` `col_4` int GENERATED ALWAYS AS (YEAR(`col_0`)),
        CHANGE `col_5` `col_5` int GENERATED ALWAYS AS (YEAR(`col_0`)) VIRTUAL,
        CHANGE `col_6` `col_6` int GENERATED ALWAYS AS (YEAR(`col_0`)) STORED
    '
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];

/** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] $columns */
$columns = array_values($command->getColumnList()->getChangedColumns());
Assert::count(7, $columns);

// generated
Assert::false($columns[0]->isGenerated());
Assert::true($columns[1]->isGenerated());
Assert::true($columns[2]->isGenerated());
Assert::true($columns[3]->isGenerated());
Assert::true($columns[4]->isGenerated());
Assert::true($columns[5]->isGenerated());
Assert::true($columns[6]->isGenerated());

// type
Assert::same(GeneratedColumnType::VIRTUAL, $columns[1]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::VIRTUAL, $columns[2]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::STORED, $columns[3]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::VIRTUAL, $columns[4]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::VIRTUAL, $columns[5]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::STORED, $columns[6]->getGeneratedColumnType()->getValue());

// expression
Assert::same('(YEAR(`col_0`))', $columns[1]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[2]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[3]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[4]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[5]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[6]->getExpression());



$commands = $parser->parse(
    'ALTER TABLE `test`
        MODIFY `col_0` int,
        MODIFY `col_1` int AS (YEAR(`col_0`)),
        MODIFY `col_2` int AS (YEAR(`col_0`)) VIRTUAL,
        MODIFY `col_3` int AS (YEAR(`col_0`)) STORED,
        MODIFY `col_4` int GENERATED ALWAYS AS (YEAR(`col_0`)),
        MODIFY `col_5` int GENERATED ALWAYS AS (YEAR(`col_0`)) VIRTUAL,
        MODIFY `col_6` int GENERATED ALWAYS AS (YEAR(`col_0`)) STORED
    '
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];

/** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] $columns */
$columns = array_values($command->getColumnList()->getColumns());
Assert::count(7, $columns);

// generated
Assert::false($columns[0]->isGenerated());
Assert::true($columns[1]->isGenerated());
Assert::true($columns[2]->isGenerated());
Assert::true($columns[3]->isGenerated());
Assert::true($columns[4]->isGenerated());
Assert::true($columns[5]->isGenerated());
Assert::true($columns[6]->isGenerated());

// type
Assert::same(GeneratedColumnType::VIRTUAL, $columns[1]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::VIRTUAL, $columns[2]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::STORED, $columns[3]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::VIRTUAL, $columns[4]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::VIRTUAL, $columns[5]->getGeneratedColumnType()->getValue());
Assert::same(GeneratedColumnType::STORED, $columns[6]->getGeneratedColumnType()->getValue());

// expression
Assert::same('(YEAR(`col_0`))', $columns[1]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[2]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[3]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[4]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[5]->getExpression());
Assert::same('(YEAR(`col_0`))', $columns[6]->getExpression());
