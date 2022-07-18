<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Sql\Ddl\Event\DropEventCommand;
use SqlFtw\Sql\Ddl\Index\DropIndexCommand;
use SqlFtw\Sql\Ddl\LogfileGroup\DropLogfileGroupCommand;
use SqlFtw\Sql\Ddl\Routine\DropFunctionCommand;
use SqlFtw\Sql\Ddl\Routine\DropProcedureCommand;
use SqlFtw\Sql\Ddl\Schema\DropSchemaCommand;
use SqlFtw\Sql\Ddl\Server\DropServerCommand;
use SqlFtw\Sql\Ddl\Table\DropTableCommand;
use SqlFtw\Sql\Ddl\Tablespace\DropTablespaceCommand;
use SqlFtw\Sql\Ddl\Trigger\DropTriggerCommand;
use SqlFtw\Sql\Ddl\View\DropViewCommand;
use Tester\Assert;

require '../../../bootstrap.php';

// phpcs:disable
\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

// drop database
$commands = $parser->parse('DROP DATABASE db1');
Assert::count(1, $commands);
Assert::type(DropSchemaCommand::class, $commands[0]);

// drop event
$commands = $parser->parse('DROP EVENT event1');
Assert::count(1, $commands);
Assert::type(DropEventCommand::class, $commands[0]);

// drop function
$commands = $parser->parse('DROP FUNCTION function1');
Assert::count(1, $commands);
Assert::type(DropFunctionCommand::class, $commands[0]);

// drop index
$commands = $parser->parse('DROP INDEX index1 ON table1');
Assert::count(1, $commands);
Assert::type(DropIndexCommand::class, $commands[0]);

// drop logfile group
$commands = $parser->parse('DROP LOGFILE GROUP group1');
Assert::count(1, $commands);
Assert::type(DropLogfileGroupCommand::class, $commands[0]);

// drop procedure
$commands = $parser->parse('DROP PROCEDURE procedure1');
Assert::count(1, $commands);
Assert::type(DropProcedureCommand::class, $commands[0]);

// drop server
$commands = $parser->parse('DROP SERVER server1');
Assert::count(1, $commands);
Assert::type(DropServerCommand::class, $commands[0]);

// drop table
$commands = $parser->parse('DROP TABLE table1');
Assert::count(1, $commands);
Assert::type(DropTableCommand::class, $commands[0]);

// drop tablespace
$commands = $parser->parse('DROP TABLESPACE tablespace1');
Assert::count(1, $commands);
Assert::type(DropTablespaceCommand::class, $commands[0]);

// drop trigger
$commands = $parser->parse('DROP TRIGGER trigger1');
Assert::count(1, $commands);
Assert::type(DropTriggerCommand::class, $commands[0]);

// drop view
$commands = $parser->parse('DROP VIEW view1');
Assert::count(1, $commands);
Assert::type(DropViewCommand::class, $commands[0]);
