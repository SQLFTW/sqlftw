<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Parser\Lexer\Lexer;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserFactory;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use Tester\Assert;

require '../../../bootstrap.php';

\Tester\Environment::skip();
// phpcs:disable
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse(
    'ALTER TABLE `test`
        ADD `col_0` int,
        ADD `col_1` int FIRST,
        ADD `col_2` int AFTER `col_0`
    '
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];

/** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] $columns */
$columns = array_values($command->getColumnList()->getAddedColumns());
Assert::count(3, $columns);

// first
Assert::false($columns[0]->isFirst());
Assert::true($columns[1]->isFirst());
Assert::false($columns[2]->isFirst());

// after
Assert::same(null, $columns[0]->getAfter());
Assert::same(false, $columns[1]->getAfter());
Assert::same('col_0', $columns[2]->getAfter());


$commands = $parser->parse(
    'ALTER TABLE `test`
        CHANGE `col_0` `col_0` int,
        CHANGE `col_1` `col_1` int FIRST,
        CHANGE `col_2` `col_2` int AFTER col_0
    '
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];

/** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] $columns */
$columns = array_values($command->getColumnList()->getChangedColumns());
Assert::count(3, $columns);

// first
Assert::false($columns[0]->isFirst());
Assert::true($columns[1]->isFirst());
Assert::false($columns[2]->isFirst());

// after
Assert::same(null, $columns[0]->getAfter());
Assert::same(false, $columns[1]->getAfter());
Assert::same('col_0', $columns[2]->getAfter());


$commands = $parser->parse(
    'ALTER TABLE `test`
        MODIFY `col_0` int,
        MODIFY `col_1` int FIRST ,
        MODIFY `col_2` int AFTER `col_0`
    '
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];

/** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] $columns */
$columns = array_values($command->getColumnList()->getColumns());
Assert::count(3, $columns);

// first
Assert::false($columns[0]->isFirst());
Assert::true($columns[1]->isFirst());
Assert::false($columns[2]->isFirst());

// after
Assert::same(null, $columns[0]->getAfter());
Assert::same(false, $columns[1]->getAfter());
Assert::same('col_0', $columns[2]->getAfter());
