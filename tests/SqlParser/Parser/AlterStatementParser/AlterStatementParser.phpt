<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use AlterExecutor\Parser\Sql\InvalidCommand;
use SqlFtw\Parser\Lexer\Lexer;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserFactory;
use SqlFtw\Sql\Ddl\Database\AlterDatabaseCommand;
use SqlFtw\Sql\Ddl\Event\AlterEventCommand;
use SqlFtw\Sql\Ddl\Routines\AlterFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\AlterProcedureCommand;
use SqlFtw\Sql\Ddl\Server\AlterServerCommand;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Tablespace\AlterTablespaceCommand;
use SqlFtw\Sql\Ddl\View\AlterViewCommand;
use Tester\Assert;

require '../../../bootstrap.php';

\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

// alter database
$commands = $parser->parse('ALTER DATABASE db1');
Assert::count(1, $commands);
Assert::type(AlterDatabaseCommand::class, $commands[0]);

// alter event
$commands = $parser->parse('ALTER EVENT event1');
Assert::count(1, $commands);
Assert::type(AlterEventCommand::class, $commands[0]);

// alter function
$commands = $parser->parse('ALTER FUNCTION function1');
Assert::count(1, $commands);
Assert::type(AlterFunctionCommand::class, $commands[0]);

// alter instance
$commands = $parser->parse('ALTER INSTANCE instance1');
Assert::count(1, $commands);
Assert::type(InvalidCommand::class, $commands[0]);

// alter logfile group
$commands = $parser->parse('ALTER LOGFILE GROUP group1');
Assert::count(1, $commands);
Assert::type(InvalidCommand::class, $commands[0]);

// alter procedure
$commands = $parser->parse('ALTER PROCEDURE procedure1');
Assert::count(1, $commands);
Assert::type(AlterProcedureCommand::class, $commands[0]);

// alter server
$commands = $parser->parse('ALTER SERVER server1');
Assert::count(1, $commands);
Assert::type(AlterServerCommand::class, $commands[0]);

// alter table
$commands = $parser->parse('ALTER TABLE table1 ADD id bigint');
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);

// alter tablespace
$commands = $parser->parse('ALTER TABLESPACE tablespace1');
Assert::count(1, $commands);
Assert::type(AlterTablespaceCommand::class, $commands[0]);

// alter view
$commands = $parser->parse('ALTER VIEW view1');
Assert::count(1, $commands);
Assert::type(AlterViewCommand::class, $commands[0]);
