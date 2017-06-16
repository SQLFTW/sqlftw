<?php

namespace AlterExecutor\Parser;

use SqlFtw\Sql\Ddl\Table\DropTableCommand;
use Tester\Assert;

require '../../../bootstrap.php';

\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse('DROP TABLE foo');
Assert::count(1, $commands);
Assert::type(DropTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\DropTableCommand $command */
$command = $commands[0];
Assert::null($command->getDatabaseName());
Assert::same('foo', $command->getTableName());

$commands = $parser->parse('DROP TABLE bAr.bAz');
Assert::count(1, $commands);
Assert::type(DropTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\DropTableCommand $command */
$command = $commands[0];
Assert::same('bAr', $command->getDatabaseName());
Assert::same('bAz', $command->getTableName());
