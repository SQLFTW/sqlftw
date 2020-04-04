<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Parser\InvalidCommand;
use SqlFtw\Parser\Lexer\Lexer;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserFactory;
use SqlFtw\Sql\Dal\User\CreateUserCommand;
use SqlFtw\Sql\Ddl\Schema\CreateSchemaCommand;
use SqlFtw\Sql\Ddl\Event\CreateEventCommand;
use SqlFtw\Sql\Ddl\Index\CreateIndexCommand;
use SqlFtw\Sql\Ddl\Routines\CreateFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\CreateProcedureCommand;
use SqlFtw\Sql\Ddl\Server\CreateServerCommand;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use SqlFtw\Sql\Ddl\Tablespace\CreateTablespaceCommand;
use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\View\CreateViewCommand;
use Tester\Assert;

require '../../../bootstrap.php';

// phpcs:disable
\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

// create database
$commands = $parser->parse('CREATE DATABASE db1');
Assert::count(1, $commands);
Assert::type(CreateSchemaCommand::class, $commands[0]);

// create event
$commands = $parser->parse('CREATE EVENT event1');
Assert::count(1, $commands);
Assert::type(CreateEventCommand::class, $commands[0]);

// create function
$commands = $parser->parse('CREATE FUNCTION function1 () RETURNS int');
Assert::count(1, $commands);
Assert::type(CreateFunctionCommand::class, $commands[0]);

// create index
$commands = $parser->parse('CREATE INDEX index1 ON table1 (column1)');
Assert::count(1, $commands);
Assert::type(CreateIndexCommand::class, $commands[0]);

// create logfile group
$commands = $parser->parse('CREATE LOGFILE GROUP group1');
Assert::count(1, $commands);
Assert::type(InvalidCommand::class, $commands[0]);

// create procedure
$commands = $parser->parse('CREATE PROCEDURE procedure1');
Assert::count(1, $commands);
Assert::type(CreateProcedureCommand::class, $commands[0]);

// create server
$commands = $parser->parse('CREATE SERVER server1');
Assert::count(1, $commands);
Assert::type(CreateServerCommand::class, $commands[0]);

// create table
$commands = $parser->parse('CREATE TABLE table1 (id bigint)');
Assert::count(1, $commands);
Assert::type(CreateTableCommand::class, $commands[0]);

// create tablespace
$commands = $parser->parse('CREATE TABLESPACE tablespace1');
Assert::count(1, $commands);
Assert::type(CreateTablespaceCommand::class, $commands[0]);

// create trigger
$commands = $parser->parse('CREATE TRIGGER trigger1');
Assert::count(1, $commands);
Assert::type(CreateTriggerCommand::class, $commands[0]);

// create user
$commands = $parser->parse('CREATE USER user1');
Assert::count(1, $commands);
Assert::type(CreateUserCommand::class, $commands[0]);

// create view
$commands = $parser->parse('CREATE VIEW view1');
Assert::count(1, $commands);
Assert::type(CreateViewCommand::class, $commands[0]);
