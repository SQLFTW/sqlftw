<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Sql\Ddl\Table\Column\GeneratedColumnType;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use Tester\Assert;

require '../../../bootstrap.php';

\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse(
    'CREATE TABLE `test` (
        `col_0` datetime,
        `col_1` int AS (YEAR(`col_0`)),
        `col_2` int AS (YEAR(`col_0`)) VIRTUAL,
        `col_3` int AS (YEAR(`col_0`)) STORED,
        `col_4` int GENERATED ALWAYS AS (YEAR(`col_0`)),
        `col_5` int GENERATED ALWAYS AS (YEAR(`col_0`)) VIRTUAL,
        `col_6` int GENERATED ALWAYS AS (YEAR(`col_0`)) STORED
    )'
);
Assert::count(1, $commands);
Assert::type(CreateTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\CreateTableCommand $command */
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
