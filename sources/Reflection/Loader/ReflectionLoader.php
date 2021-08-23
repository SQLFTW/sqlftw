<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection\Loader;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\Parser;
use SqlFtw\Reflection\TableLoadingFailedException;
use SqlFtw\Reflection\ViewLoadingFailedException;
use SqlFtw\Sql\Ddl\Event\CreateEventCommand;
use SqlFtw\Sql\Ddl\Routines\CreateFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\CreateProcedureCommand;
use SqlFtw\Sql\Ddl\Schema\CreateSchemaCommand;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use SqlFtw\Sql\Ddl\Tablespace\CreateTablespaceCommand;
use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\View\CreateViewCommand;
use SqlFtw\Sql\QualifiedName;
use Throwable;
use function count;

class ReflectionLoader
{
    use StrictBehaviorMixin;

    /** @var Parser */
    private $parser;

    /** @var ContextProvider */
    private $provider;

    public function __construct(Parser $parser, ContextProvider $provider)
    {
        $this->parser = $parser;
        $this->provider = $provider;
    }

    public function getCreateSchemaCommand(string $name): CreateSchemaCommand
    {
        // todo
    }

    public function getCreateTablespaceCommand(string $name): CreateTablespaceCommand
    {
        // todo
    }

    public function getCreateTableCommand(QualifiedName $table): CreateTableCommand
    {
        try {
            $sql = $this->provider->getCreateTable($table->getName(), $table->getSchema());
        } catch (Throwable $e) {
            throw new TableLoadingFailedException($table, null, $e);
        }

        /** @var CreateTableCommand[] $commands */
        $commands = $this->parser->parse($sql);

        if (count($commands) > 1) {
            throw new TableLoadingFailedException($table, 'More than one command parsed. One CREATE TABLE command expected.');
        } elseif (count($commands) < 1) {
            throw new TableLoadingFailedException($table, 'No command parsed. One CREATE TABLE command expected.');
        } elseif (!$commands[0] instanceof CreateTableCommand) {
            throw new TableLoadingFailedException($table, 'Unexpected command parsed. One CREATE TABLE command expected.');
        }

        return $commands[0];
    }

    public function getCreateViewCommand(QualifiedName $view): CreateViewCommand
    {
        try {
            $sql = $this->provider->getCreateView($view->getName(), $view->getSchema());
        } catch (Throwable $e) {
            throw new ViewLoadingFailedException($view, 'Loading error.', $e);
        }

        /** @var CreateViewCommand[] $commands */
        $commands = $this->parser->parse($sql);

        if (count($commands) > 1) {
            throw new ViewLoadingFailedException($view, 'More than one command parsed. One CREATE VIEW command expected.');
        } elseif (count($commands) < 1) {
            throw new ViewLoadingFailedException($view, 'No command parsed. One CREATE VIEW command expected.');
        } elseif (!$commands[0] instanceof CreateViewCommand) {
            throw new ViewLoadingFailedException($view, 'Unexpected command parsed. One CREATE VIEW command expected.');
        }

        return $commands[0];
    }

    public function getCreateFunctionCommand(QualifiedName $function): CreateFunctionCommand
    {
        // todo
    }

    public function getCreateProcedureCommand(QualifiedName $procedure): CreateProcedureCommand
    {
        // todo
    }

    public function getCreateTriggerCommand(QualifiedName $trigger): CreateTriggerCommand
    {
        // todo
    }

    public function getCreateEventCommand(QualifiedName $event): CreateEventCommand
    {
        // todo
    }

}
