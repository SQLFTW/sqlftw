<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use Tester\Assert;

require '../../../bootstrap.php';

\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse(
    'ALTER TABLE `test`
    RENAME TO foo'
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];
Assert::null($command->getNewTable()->getDatabaseName());
Assert::same('foo', $command->getNewTable()->getName());


$commands = $parser->parse(
    'ALTER TABLE `test`
    RENAME TO foo.bar'
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];
Assert::same('foo', $command->getNewTable()->getDatabaseName());
Assert::same('bar', $command->getNewTable()->getName());

$commands = $parser->parse(
    'ALTER TABLE `test`
    RENAME AS foo'
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];
Assert::null($command->getNewTable()->getDatabaseName());
Assert::same('foo', $command->getNewTable()->getName());


$commands = $parser->parse(
    'ALTER TABLE `test`
    RENAME AS foo.bar'
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];
Assert::same('foo', $command->getNewTable()->getDatabaseName());
Assert::same('bar', $command->getNewTable()->getName());


$commands = $parser->parse(
    'ALTER TABLE `test`
    RENAME foo'
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];
Assert::null($command->getNewTable()->getDatabaseName());
Assert::same('foo', $command->getNewTable()->getName());


$commands = $parser->parse(
    'ALTER TABLE `test`
    RENAME foo.bar'
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];
Assert::same('foo', $command->getNewTable()->getDatabaseName());
Assert::same('bar', $command->getNewTable()->getName());
