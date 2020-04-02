<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Sql\Ddl\Table\TruncateTableCommand;
use Tester\Assert;

require '../../../bootstrap.php';

// phpcs:disable
\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse('TRUNCATE TABLE foo');
Assert::count(1, $commands);
Assert::type(TruncateTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\TruncateTableCommand $command */
$command = $commands[0];
Assert::null($command->getDatabaseName());
Assert::same('foo', $command->getName());

$commands = $parser->parse('TRUNCATE TABLE bAr.bAz');
Assert::count(1, $commands);
Assert::type(TruncateTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\TruncateTableCommand $command */
$command = $commands[0];
Assert::same('bAr', $command->getDatabaseName());
Assert::same('bAz', $command->getName());
