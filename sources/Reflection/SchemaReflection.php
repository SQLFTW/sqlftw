<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Event\AlterEventCommand;
use SqlFtw\Sql\Ddl\Event\CreateEventCommand;
use SqlFtw\Sql\Ddl\Event\DropEventCommand;
use SqlFtw\Sql\Ddl\Index\CreateIndexCommand;
use SqlFtw\Sql\Ddl\Index\DropIndexCommand;
use SqlFtw\Sql\Ddl\Routines\AlterFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\AlterProcedureCommand;
use SqlFtw\Sql\Ddl\Routines\CreateFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\CreateProcedureCommand;
use SqlFtw\Sql\Ddl\Routines\DropFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\DropProcedureCommand;
use SqlFtw\Sql\Ddl\Schema\AlterSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\CreateSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\DropSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\SchemaCommand;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use SqlFtw\Sql\Ddl\Table\DropTableCommand;
use SqlFtw\Sql\Ddl\Table\RenameTableCommand;
use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\DropTriggerCommand;
use SqlFtw\Sql\Ddl\View\AlterViewCommand;
use SqlFtw\Sql\Ddl\View\CreateViewCommand;
use SqlFtw\Sql\Ddl\View\DropViewCommand;
use SqlFtw\Sql\QualifiedName;
use function end;

class SchemaReflection
{
    use StrictBehaviorMixin;

    /** @var DatabaseReflection */
    private $database;

    /** @var bool */
    private $trackHistory;

    /** @var self|null */
    private $previous;

    /** @var SchemaCommand */
    private $lastCommand;

    /** @var string */
    private $name;

    /** @var TableReflection[] */
    private $tables = [];

    /** @var ViewReflection[] */
    private $views = [];

    /** @var FunctionReflection[] */
    private $functions = [];

    /** @var ProcedureReflection[] */
    private $procedures = [];

    /** @var EventReflection[] */
    private $events = [];

    /** @var TriggerReflection[] */
    private $triggers = [];

    public function __construct(
        DatabaseReflection $database,
        CreateSchemaCommand $createSchemaCommand,
        bool $trackHistory
    ) {
        $this->database = $database;
        $this->name = $createSchemaCommand->getName();
        $this->lastCommand = $createSchemaCommand;
        $this->trackHistory = $trackHistory;
    }

    public function apply(Command $command): self
    {
        if ($command instanceof AlterSchemaCommand) {
            // todo charset, collation

            return $this;
        } elseif ($command instanceof DropSchemaCommand) {
            $that = clone $this;
            $that->lastCommand = $command;
            if ($this->trackHistory) {
                $that->previous = $this;
            }

            return $that;
        } elseif ($command instanceof CreateTableCommand) {
            $name = $command->getName();
            if ($this->database->tableExists($name)) {
                throw new TableAlreadyExistsException($name);
            }

            $that = clone $this;
            $reflection = new TableReflection($that, $command, $this->trackHistory);
            $that->tables[$name->getName()] = $reflection;
            if ($this->trackHistory) {
                $that->previous = $this;
            }

            return $that;
        } elseif ($command instanceof AlterTableCommand) {
            $that = clone $this;
            $name = $command->getName();

            $renameAction = $command->getRenameAction();
            if ($renameAction !== null) {
                /** @var QualifiedName $newName */
                $newName = $renameAction->getNewName();
                $newName = $newName->coalesce($this->name);

                $newSchema = $newName->getSchema();
                $oldReflection = $this->database->findTable($newName);
                if ($oldReflection !== null) {
                    throw new TableAlreadyExistsException($newName);
                }

                $that->tables[$newSchema][$newName] = $reflection->alter($command);
                unset($that->tables[$schema][$name]);
                if ($this->history !== null) {
                    $that->history = clone($this->history);
                    $that->history->tables[$schema][$name] = $reflection->moveByRenaming($command);
                    unset($that->history->tables[$newSchema][$newName]);
                }
            } else {
                $that->tables[$schema][$name] = $reflection->alter($command);
            }

            return $that;
        } elseif ($command instanceof RenameTableCommand) {
            $that = clone $this;
            /**
             * @var QualifiedName $oldTable
             * @var QualifiedName $newTable
             */
            foreach ($command->getIterator() as $oldTable => $newTable) {
                $name = $oldTable->getName();
                $schema = $oldTable->getSchema() ?: $this->currentSchema;
                $reflection = $this->getTable($name, $schema);

                $newName = $newTable->getName();
                $newSchema = $newTable->getSchema() ?: $this->currentSchema;
                $reflection = $this->findTable($newName, $newSchema);
                if ($reflection !== null) {
                    throw new TableAlreadyExistsException($newTable->getName(), $newSchema);
                }

                $that->tables[$newSchema][$newName] = $reflection->rename($command);
                unset($that->tables[$schema][$name]);
                if ($this->history !== null) {
                    $that->history = clone($this->history);
                    $that->history->tables[$schema][$name] = $reflection->moveByRenaming($command);
                    unset($that->history->tables[$newSchema][$newName]);
                }
            }

            return $that;
        } elseif ($command instanceof DropTableCommand) {
            $that = clone $this;
            if ($this->history !== null) {
                $that->history = clone($this->history);
            }
            foreach ($command->getNames() as $table) {
                $name = $table->getName();
                $schema = $table->getSchema() ?: $this->currentSchema;
                $reflection = $this->getTable($name, $schema);
                unset($that->tables[$schema][$name]);
                if ($this->history !== null) {
                    $that->tables[$schema][$name] = $reflection->drop($command);
                }
            }

            return $that;
        } elseif ($command instanceof CreateIndexCommand) {
            $that = clone $this;
            $table = $command->getTable();
            $name = $table->getName();
            $schema = $table->getSchema() ?: $this->currentSchema;

            $reflection = $this->getTable($name, $schema);
            $that->tables[$schema][$name] = $reflection->createIndex($command);

            return $that;
        } elseif ($command instanceof DropIndexCommand) {
            $that = clone $this;
            $table = $command->getTable();
            $name = $table->getName();
            $schema = $table->getSchema() ?: $this->currentSchema;

            $reflection = $this->getTable($name, $schema);
            $that->tables[$schema][$name] = $reflection->dropIndex($command);

            return $that;
        } elseif ($command instanceof CreateViewCommand) {
            $that = clone $this;
            $view = $command->getName();
            $name = $view->getName();
            $schema = $view->getSchema() ?: $this->currentSchema;

            $reflection = $this->findView($name, $schema);
            if ($reflection !== null) {
                throw new ViewAlreadyExistsException($name, $schema);
            }

            $reflection = new ViewReflection(new QualifiedName($name, $schema), $command);
            $that->views[$schema][$name] = $reflection;
            if ($this->history !== null) {
                $that->history = clone($this->history);
                unset($that->history->views[$schema][$name]);
            }

            return $that;
        } elseif ($command instanceof AlterViewCommand) {
            $that = clone $this;
            $view = $command->getName();
            $name = $view->getName();
            $schema = $view->getSchema() ?: $this->currentSchema;

            $reflection = $this->getView($name, $schema);
            $that->views[$schema][$name] = $reflection->alter($command);

            return $that;
        } elseif ($command instanceof DropViewCommand) {
            $that = clone $this;
            foreach ($command->getNames() as $view) {
                $name = $view->getName();
                $schema = $view->getSchema() ?: $this->currentSchema;
                $reflection = $this->getView($name, $schema);
                unset($that->views[$schema][$name]);
                if ($this->history !== null) {
                    $that->history = clone($this->history);
                    $that->history->views[$schema][$name] = $reflection->drop($command);
                }
            }

            return $that;
        } elseif ($command instanceof CreateFunctionCommand) {
            $that = clone $this;
            $function = $command->getName();
            $name = $function->getName();
            $schema = $function->getSchema() ?: $this->currentSchema;

            $reflection = $this->findFunction($name, $schema);
            if ($reflection !== null) {
                throw new FunctionAlreadyExistsException($name, $schema);
            }
            $reflection = $this->findProcedure($name, $schema);
            if ($reflection !== null) {
                throw new ProcedureAlreadyExistsException($name, $schema);
            }

            $reflection = new FunctionReflection(new QualifiedName($name, $schema), $command);
            $that->functions[$schema][$name] = $reflection;
            if ($this->history !== null) {
                $that->history = clone($this->history);
                unset($that->history->functions[$schema][$name]);
                unset($that->history->procedures[$schema][$name]);
            }

            return $that;
        } elseif ($command instanceof AlterFunctionCommand) {
            $that = clone $this;
            $function = $command->getName();
            $name = $function->getName();
            $schema = $function->getSchema() ?: $this->currentSchema;

            $reflection = $this->getFunction($name, $schema);
            $that->functions[$schema][$name] = $reflection->alter($command);

            return $that;
        } elseif ($command instanceof DropFunctionCommand) {
            $that = clone $this;
            $function = $command->getName();
            $name = $function->getName();
            $schema = $function->getSchema() ?: $this->currentSchema;
            $reflection = $this->getFunction($name, $schema);
            unset($this->functions[$schema][$name]);
            if ($this->history !== null) {
                $that->history = clone($this->history);
                $that->history->functions[$schema][$name] = $reflection->drop($command);
            }

            return $that;
        } elseif ($command instanceof CreateProcedureCommand) {
            $that = clone $this;
            $procedure = $command->getName();
            $name = $procedure->getName();
            $schema = $procedure->getSchema() ?: $this->currentSchema;

            $reflection = $this->findFunction($name, $schema);
            if ($reflection !== null) {
                throw new FunctionAlreadyExistsException($name, $schema);
            }
            $reflection = $this->findProcedure($name, $schema);
            if ($reflection !== null) {
                throw new ProcedureAlreadyExistsException($name, $schema);
            }

            $reflection = new ProcedureReflection(new QualifiedName($name, $schema), $command);
            $that->procedures[$schema][$name] = $reflection;
            if ($this->history !== null) {
                $that->history = clone($this->history);
                unset($that->history->functions[$schema][$name]);
                unset($that->history->procedures[$schema][$name]);
            }

            return $that;
        } elseif ($command instanceof AlterProcedureCommand) {
            $that = clone $this;
            $procedure = $command->getName();
            $name = $procedure->getName();
            $schema = $procedure->getSchema() ?: $this->currentSchema;

            $reflection = $this->getProcedure($name, $schema);
            $that->procedures[$schema][$name] = $reflection->alter($command);

            return $that;
        } elseif ($command instanceof DropProcedureCommand) {
            $that = clone $this;
            $procedure = $command->getName();
            $name = $procedure->getName();
            $schema = $procedure->getSchema() ?: $this->currentSchema;
            $reflection = $this->getProcedure($name, $schema);
            unset($that->procedures[$schema][$name]);
            if ($this->history !== null) {
                $that->history = clone($this->history);
                $that->procedures[$schema][$name] = $reflection->drop($command);
            }

            return $that;
        } elseif ($command instanceof CreateTriggerCommand) {
            $that = clone $this;
            $name = $command->getName();
            $table = $command->getTable();
            $tableName = $table->getName();
            $schema = $table->getSchema() ?: $this->currentSchema;

            $tableReflection = $this->getTable($tableName, $schema);
            $reflection = $this->findTrigger($name, $schema);
            if ($reflection !== null) {
                throw new TriggerAlreadyExistsException($name, $schema);
            }

            $reflection = new TriggerReflection(new QualifiedName($name, $schema), $command);
            $that->triggers[$schema][$name] = $reflection;
            $that->tables[$schema][$tableName] = $tableReflection->createTrigger($reflection);
            if ($this->history !== null) {
                $that->history = clone($this->history);
                unset($that->history->triggers[$schema][$name]);
            }

            return $that;
        } elseif ($command instanceof DropTriggerCommand) {
            $that = clone $this;
            $trigger = $command->getName();
            $name = $trigger->getName();
            $schema = $trigger->getSchema() ?: $this->currentSchema;

            $reflection = $this->getTrigger($name, $schema);
            $table = $reflection->getTable();
            $tableName = $table->getName();
            $tableReflection = $this->getTable($tableName, $schema);

            unset($that->triggers[$schema][$name]);
            $this->tables[$schema][$tableName] = $tableReflection->dropTrigger($name);
            if ($this->history !== null) {
                $that->history = clone($this->history);
                $that->triggers[$schema][$name] = $reflection->drop($command);
            }

            return $that;
        } elseif ($command instanceof CreateEventCommand) {
            $that = clone $this;
            $event = $command->getName();
            $name = $event->getName();
            $schema = $event->getSchema() ?: $this->currentSchema;

            $reflection = $this->findEvent($name, $schema);
            if ($reflection !== null) {
                throw new EventAlreadyExistsException($name, $schema);
            }

            $reflection = new EventReflection(new QualifiedName($name, $schema), $command);
            $that->events[$schema][$name] = $reflection;
            if ($this->history !== null) {
                $that->history = clone($this->history);
                unset($that->history->events[$schema][$name]);
            }

            return $that;
        } elseif ($command instanceof AlterEventCommand) {
            $that = clone $this;
            $event = $command->getName();
            $name = $event->getName();
            $schema = $event->getSchema() ?: $this->currentSchema;

            $reflection = $this->getEvent($name, $schema);
            $that->events[$schema][$name] = $reflection->alter($command);

            return $that;
        } elseif ($command instanceof DropEventCommand) {
            $that = clone $this;
            $event = $command->getName();
            $name = $event->getName();
            $schema = $event->getSchema() ?: $this->currentSchema;

            $reflection = $this->getEvent($name, $schema);
            unset($that->events[$schema][$name]);
            if ($this->history !== null) {
                $that->history = clone($this->history);
                $that->events[$schema][$name] = $reflection->drop($command);
            }

            return $that;
        } else {
            throw new ShouldNotHappenException('Unknown command.');
        }
    }

    public function getPrevious(): ?self
    {
        return $this->previous;
    }

    public function getDatabase(): DatabaseReflection
    {
        return $this->database;
    }

    public function getPlatform(): Platform
    {
        return $this->database->getPlatform();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function wasDropped(): bool
    {
        return end($this->lastCommand) instanceof DropSchemaCommand;
    }

    public function wasRenamed(): bool
    {
        // no way to do that in MySQL
        return false;
    }

    public function getLastCommand(): Command
    {
        return end($this->lastCommand);
    }

    /**
     * @return SchemaCommand[]
     */
    public function getCommands(): array
    {
        return $this->lastCommand;
    }

    public function getTable(string $name): TableReflection
    {
        return $this->tables[$name] ?? $this->database->getTable($name, $this->name);
    }

    public function getTableIfLoaded(string $name): ?TableReflection
    {
        return $this->tables[$name] ?? null;
    }

    public function getView(string $name): ViewReflection
    {
        return $this->views[$name] ?? $this->database->getView($name, $this->name);
    }

    public function getViewIfLoaded(string $name): ?ViewReflection
    {
        return $this->views[$name] ?? null;
    }

    public function getFunction(string $name): FunctionReflection
    {
        return $this->functions[$name] ?? $this->database->getFunction($name, $this->name);
    }

    public function getFunctionIfLoaded(string $name): ?FunctionReflection
    {
        return $this->functions[$name] ?? null;
    }

    public function getProcedure(string $name): ProcedureReflection
    {
        return $this->procedures[$name] ?? $this->database->getProcedure($name, $this->name);
    }

    public function getProcedureIfLoaded(string $name): ?ProcedureReflection
    {
        return $this->procedures[$name] ?? null;
    }

    public function getTrigger(string $name): TriggerReflection
    {
        return $this->triggers[$name] ?? $this->database->getTrigger($name, $this->name);
    }

    public function getTriggerIfLoaded(string $name): ?TriggerReflection
    {
        return $this->triggers[$name] ?? null;
    }

    public function getEvent(string $name): EventReflection
    {
        return $this->events[$name] ?? $this->database->getEvent($name, $this->name);
    }

    public function getEventIfLoaded(string $name): ?EventReflection
    {
        return $this->events[$name] ?? null;
    }

}
