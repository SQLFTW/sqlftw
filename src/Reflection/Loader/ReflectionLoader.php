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
use SqlFtw\Reflection\Context\ContextProvider;
use SqlFtw\Reflection\TableDoesNotExistException;
use SqlFtw\Reflection\TableReflectionLoadingException;
use SqlFtw\Reflection\ViewReflectionLoadingException;
use SqlFtw\Sql\Ddl\Schema\CreateSchemaCommand;
use SqlFtw\Sql\Ddl\Event\CreateEventCommand;
use SqlFtw\Sql\Ddl\Routines\CreateFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\CreateProcedureCommand;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\View\CreateViewCommand;
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

    public function getCreateTableCommand(string $name, string $schema): CreateTableCommand
    {
        try {
            $sql = $this->provider->getCreateTable($schema, $name);
        } catch (TableDoesNotExistException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new TableReflectionLoadingException($name, $schema, 'Loading error.', $e);
        }

        /** @var CreateTableCommand[] $commands */
        $commands = $this->parser->parse($sql);

        if (count($commands) > 1) {
            throw new TableReflectionLoadingException($name, $schema, 'More than one command parsed. One CREATE TABLE command expected.');
        } elseif (count($commands) < 1) {
            throw new TableReflectionLoadingException($name, $schema, 'No command parsed. One CREATE TABLE command expected.');
        } elseif (!$commands[0] instanceof CreateTableCommand) {
            throw new TableReflectionLoadingException($name, $schema, 'Unexpected command parsed. One CREATE TABLE command expected.');
        }

        return $commands[0];
    }

    public function getCreateViewCommand(string $name, string $schema): CreateViewCommand
    {
        try {
            $sql = $this->provider->getCreateTable($schema, $name);
        } catch (TableDoesNotExistException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ViewReflectionLoadingException($name, $schema, 'Loading error.', $e);
        }

        /** @var CreateViewCommand[] $commands */
        $commands = $this->parser->parse($sql);

        if (count($commands) > 1) {
            throw new ViewReflectionLoadingException($name, $schema, 'More than one command parsed. One CREATE VIEW command expected.');
        } elseif (count($commands) < 1) {
            throw new ViewReflectionLoadingException($name, $schema, 'No command parsed. One CREATE VIEW command expected.');
        } elseif (!$commands[0] instanceof CreateViewCommand) {
            throw new ViewReflectionLoadingException($name, $schema, 'Unexpected command parsed. One CREATE VIEW command expected.');
        }

        return $commands[0];
    }

    public function getCreateFunctionCommand(string $name, string $schema): CreateFunctionCommand
    {
        // todo
    }

    public function getCreateProcedureCommand(string $name, string $schema): CreateProcedureCommand
    {
        // todo
    }

    public function getCreateTriggerCommand(string $name, string $schema): CreateTriggerCommand
    {
        // todo
    }

    public function getCreateEventCommand(string $name, string $schema): CreateEventCommand
    {
        // todo
    }

}
