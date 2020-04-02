<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Sql\Ddl\Table\RenameTableCommand;
use Tester\Assert;

require '../../../bootstrap.php';

// phpcs:disable
\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse('RENAME TABLE foo TO bar');
Assert::count(1, $commands);
Assert::type(RenameTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\RenameTableCommand $command */
$command = $commands[0];
Assert::null($command->getDatabaseName());
Assert::same('foo', $command->getTableName());
Assert::null($command->getNewDatabaseName());
Assert::same('bar', $command->getNewTableName());

$commands = $parser->parse('RENAME TABLE fOo.bAr TO foO.bAz');
Assert::count(1, $commands);
Assert::type(RenameTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\RenameTableCommand $command */
$command = $commands[0];
Assert::same('fOo', $command->getDatabaseName());
Assert::same('bAr', $command->getTableName());
Assert::same('foO', $command->getNewDatabaseName());
Assert::same('bAz', $command->getNewTableName());
