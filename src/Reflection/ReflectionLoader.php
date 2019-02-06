<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\Parser;
use SqlFtw\Reflection\Context\ContextProvider;
use SqlFtw\Sql\Ddl\Database\CreateDatabaseCommand;
use SqlFtw\Sql\Ddl\Event\CreateEventCommand;
use SqlFtw\Sql\Ddl\Routines\CreateFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\CreateProcedureCommand;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\View\CreateViewCommand;
use function count;

class ReflectionLoader
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\Parser */
    private $parser;

    /** @var \SqlFtw\Reflection\Context\ContextProvider */
    private $provider;

    public function __construct(Parser $parser, ContextProvider $provider)
    {
        $this->parser = $parser;
        $this->provider = $provider;
    }

    public function getCreateDatabaseCommand(string $name): CreateDatabaseCommand
    {
        ///
    }

    public function getCreateTableCommand(string $name, string $schema): CreateTableCommand
    {
        try {
            $sql = $this->provider->getCreateTable($schema, $name);
        } catch (\SqlFtw\Reflection\TableDoesNotExistException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \SqlFtw\Reflection\TableReflectionLoadingException($name, $schema, 'Loading error.', $e);
        }

        /** @var \SqlFtw\Sql\Ddl\Table\CreateTableCommand[] $commands */
        $commands = $this->parser->parse($sql);

        if (count($commands) > 1) {
            throw new \SqlFtw\Reflection\TableReflectionLoadingException($name, $schema, 'More than one command parsed. One CREATE TABLE command expected.');
        } elseif (count($commands) < 1) {
            throw new \SqlFtw\Reflection\TableReflectionLoadingException($name, $schema, 'No command parsed. One CREATE TABLE command expected.');
        } elseif (!$commands[0] instanceof CreateTableCommand) {
            throw new \SqlFtw\Reflection\TableReflectionLoadingException($name, $schema, 'Unexpected command parsed. One CREATE TABLE command expected.');
        }

        return $commands[0];
    }

    public function getCreateViewCommand(string $name, string $schema): CreateViewCommand
    {
        try {
            $sql = $this->provider->getCreateTable($schema, $name);
        } catch (\SqlFtw\Reflection\TableDoesNotExistException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \SqlFtw\Reflection\ViewReflectionLoadingException($name, $schema, 'Loading error.', $e);
        }

        /** @var \SqlFtw\Sql\Ddl\View\CreateViewCommand[] $commands */
        $commands = $this->parser->parse($sql);

        if (count($commands) > 1) {
            throw new \SqlFtw\Reflection\ViewReflectionLoadingException($name, $schema, 'More than one command parsed. One CREATE VIEW command expected.');
        } elseif (count($commands) < 1) {
            throw new \SqlFtw\Reflection\ViewReflectionLoadingException($name, $schema, 'No command parsed. One CREATE VIEW command expected.');
        } elseif (!$commands[0] instanceof CreateTableCommand) {
            throw new \SqlFtw\Reflection\ViewReflectionLoadingException($name, $schema, 'Unexpected command parsed. One CREATE VIEW command expected.');
        }

        return $commands[0];
    }

    public function getCreateFunctionCommand(string $name, string $schema): CreateFunctionCommand
    {
        ///
    }

    public function getCreateProcedureCommand(string $name, string $schema): CreateProcedureCommand
    {
        ///
    }

    public function getCreateTriggerCommand(string $name, string $schema): CreateTriggerCommand
    {
        ///
    }

    public function getCreateEventCommand(string $name, string $schema): CreateEventCommand
    {
        ///
    }

}
