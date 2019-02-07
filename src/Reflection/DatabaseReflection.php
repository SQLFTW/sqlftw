<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\Arr;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Database\AlterDatabaseCommand;
use SqlFtw\Sql\Ddl\Database\CreateDatabaseCommand;
use SqlFtw\Sql\Ddl\Database\DropDatabaseCommand;
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
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableActionType;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use SqlFtw\Sql\Ddl\Table\DropTableCommand;
use SqlFtw\Sql\Ddl\Table\RenameTableCommand;
use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\DropTriggerCommand;
use SqlFtw\Sql\Ddl\View\AlterViewCommand;
use SqlFtw\Sql\Ddl\View\CreateViewCommand;
use SqlFtw\Sql\Ddl\View\DropViewCommand;
use SqlFtw\Sql\Dml\Utility\UseCommand;
use SqlFtw\Sql\QualifiedName;
use function count;

/**
 * Represents whole "database" layer, not just a single schema.
 */
class DatabaseReflection
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Platform\Platform */
    private $platform;

    /** @var \SqlFtw\Reflection\ReflectionLoader */
    private $loader;

    /** @var string */
    private $currentSchema;

    /** @var \SqlFtw\Reflection\DatabaseReflection */
    private $history;

    /** @var \SqlFtw\Reflection\SchemaReflection[] */
    private $schemas = [];

    /** @var \SqlFtw\Reflection\TableReflection[][] */
    private $tables = [];

    /** @var \SqlFtw\Reflection\ViewReflection[][] */
    private $views = [];

    /** @var \SqlFtw\Reflection\FunctionReflection[][] */
    private $functions = [];

    /** @var \SqlFtw\Reflection\ProcedureReflection[][] */
    private $procedures = [];

    /** @var \SqlFtw\Reflection\TriggerReflection[][] */
    private $triggers = [];

    /** @var \SqlFtw\Reflection\EventReflection[][] */
    private $events = [];

    /** @var \SqlFtw\Reflection\VariablesReflection[] */
    private $variables = [];

    public function __construct(Platform $platform, ReflectionLoader $loader, string $currentSchema, bool $trackHistory = true)
    {
        $this->platform = $platform;
        $this->loader = $loader;
        $this->currentSchema = $currentSchema;
        if ($trackHistory) {
            $this->history = new static($platform, $loader, $currentSchema, false);
        }
    }

    public function applyCommand(Command $command): self
    {
        if ($command instanceof CreateTableCommand) {
            $that = clone($this);
            $table = $command->getTable();
            $name = $table->getName();
            $schema = $table->getSchema() ?: $this->currentSchema;

            $reflection = $this->findTable($name, $schema);
            if ($reflection !== null) {
                throw new TableAlreadyExistsException($name, $schema);
            }

            $reflection = new TableReflection($that, new QualifiedName($name, $schema), $command);
            $that->tables[$schema][$name] = $reflection;
            if ($this->history !== null) {
                $that->history = clone($this->history);
                unset($that->history->tables[$schema][$name]);
            }

            return $that;
        } elseif ($command instanceof AlterTableCommand) {
            $that = clone($this);
            $table = $command->getTable();
            $name = $table->getName();
            $schema = $table->getSchema() ?: $this->currentSchema;

            $reflection = $this->getTable($name, $schema);
            /** @var \SqlFtw\Sql\Ddl\Table\Alter\SimpleAction[] $actions */
            $actions = $command->getActions()->getActionsByType(AlterTableActionType::get(AlterTableActionType::RENAME_TO));
            if (count($actions) > 0) {
                /** @var \SqlFtw\Sql\QualifiedName $newTable */
                $newTable = $actions[0]->getValue();
                $newName = $newTable->getName();
                $newSchema = $newTable->getSchema() ?: $this->currentSchema;
                $newReflection = $this->findTable($newName, $newSchema);
                if ($newReflection !== null) {
                    throw new TableAlreadyExistsException($newTable->getName(), $newSchema);
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
            $that = clone($this);
            /**
             * @var \SqlFtw\Sql\QualifiedName $oldTable
             * @var \SqlFtw\Sql\QualifiedName $newTable
             */
            foreach ($command->getIterator() as $oldTable => $newTable) {
                $name = $oldTable->getName();
                $schema = $oldTable->getSchema() ?: $this->currentSchema;
                $reflection = $this->getTable($name, $schema);

                $newName = $newTable->getName();
                $newSchema = $newTable->getSchema() ?: $this->currentSchema;
                $newReflection = $this->findTable($newName, $newSchema);
                if ($newReflection !== null) {
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
            $that = clone($this);
            if ($this->history !== null) {
                $that->history = clone($this->history);
            }
            foreach ($command->getTables() as $table) {
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
            $that = clone($this);
            $table = $command->getTable();
            $name = $table->getName();
            $schema = $table->getSchema() ?: $this->currentSchema;

            $reflection = $this->getTable($name, $schema);
            $that->tables[$schema][$name] = $reflection->createIndex($command);

            return $that;
        } elseif ($command instanceof DropIndexCommand) {
            $that = clone($this);
            $table = $command->getTable();
            $name = $table->getName();
            $schema = $table->getSchema() ?: $this->currentSchema;

            $reflection = $this->getTable($name, $schema);
            $that->tables[$schema][$name] = $reflection->dropIndex($command);

            return $that;
        } elseif ($command instanceof CreateViewCommand) {
            $that = clone($this);
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
            $that = clone($this);
            $view = $command->getName();
            $name = $view->getName();
            $schema = $view->getSchema() ?: $this->currentSchema;

            $reflection = $this->getView($name, $schema);
            $that->views[$schema][$name] = $reflection->alter($command);

            return $that;
        } elseif ($command instanceof DropViewCommand) {
            $that = clone($this);
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
            $that = clone($this);
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
            $that = clone($this);
            $function = $command->getName();
            $name = $function->getName();
            $schema = $function->getSchema() ?: $this->currentSchema;

            $reflection = $this->getFunction($name, $schema);
            $that->functions[$schema][$name] = $reflection->alter($command);

            return $that;
        } elseif ($command instanceof DropFunctionCommand) {
            $that = clone($this);
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
            $that = clone($this);
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
            $that = clone($this);
            $procedure = $command->getName();
            $name = $procedure->getName();
            $schema = $procedure->getSchema() ?: $this->currentSchema;

            $reflection = $this->getProcedure($name, $schema);
            $that->procedures[$schema][$name] = $reflection->alter($command);

            return $that;
        } elseif ($command instanceof DropProcedureCommand) {
            $that = clone($this);
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
            $that = clone($this);
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
            $that = clone($this);
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
            $that = clone($this);
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
            $that = clone($this);
            $event = $command->getName();
            $name = $event->getName();
            $schema = $event->getSchema() ?: $this->currentSchema;

            $reflection = $this->getEvent($name, $schema);
            $that->events[$schema][$name] = $reflection->alter($command);

            return $that;
        } elseif ($command instanceof DropEventCommand) {
            $that = clone($this);
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
        } elseif ($command instanceof CreateDatabaseCommand) {
            $that = clone($this);
            $name = $command->getName();
            $schema = $this->findSchema($name);
            if ($schema !== null) {
                throw new SchemaAlreadyExistsException($name);
            }

            $reflection = new SchemaReflection($name, $command);
            $that->schemas[$name] = $reflection;
            if ($this->history !== null) {
                $that->history = clone($this->history);
                unset($that->history->schemas[$name]);
            }

            return $that;
        } elseif ($command instanceof AlterDatabaseCommand) {
            $that = clone($this);
            $name = $command->getName();

            $reflection = $this->getSchema($name);
            $that->schemas[$name] = $reflection->alter($command);

            return $that;
        } elseif ($command instanceof DropDatabaseCommand) {
            $that = clone($this);
            $name = $command->getName();

            $reflection = $this->getSchema($name);
            unset($that->schemas[$name]);
            unset($that->tables[$name]);
            unset($that->views[$name]);
            unset($that->functions[$name]);
            unset($that->procedures[$name]);
            unset($that->triggers[$name]);
            unset($that->events[$name]);
            if ($this->history !== null) {
                $that->history = clone($this->history);
                $that->history->schemas[$name] = $reflection->drop($command);
            }

            return $that;
        } elseif ($command instanceof UseCommand) {
            $schema = $command->getSchema();
            $this->useSchema($schema);

            return $this;
        } else {
            return $this;
        }
    }

    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    public function useSchema(string $schema): void
    {
        $this->getSchema($schema);
        $this->currentSchema = $schema;
    }

    /**
     * @return \SqlFtw\Reflection\SchemaReflection[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function getSchema(string $name): SchemaReflection
    {
        if (isset($this->schemas[$name])) {
            return $this->schemas[$name];
        }
        $previous = $this->history->schemas[$name] ?? null;
        if ($previous !== null) {
            if ($previous->wasDropped()) {
                throw new SchemaWasDroppedException($previous);
            }
        }

        $createDatabaseCommand = $this->loader->getCreateDatabaseCommand($name);
        $reflection = new SchemaReflection($name, $createDatabaseCommand);
        $this->schemas[$name] = $reflection;

        return $reflection;
    }

    public function findSchema(string $name): ?SchemaReflection
    {
        try {
            return $this->getSchema($name);
        } catch (SchemaDoesNotExistException $e) {
            return null;
        }
    }

    /**
     * @param string|null $schema
     * @return \SqlFtw\Reflection\TableReflection[]
     */
    public function getTables(?string $schema = null): array
    {
        if ($schema !== null) {
            $this->getSchema($schema);
            return $this->tables[$schema];
        } else {
            return Arr::flatten($this->tables);
        }
    }

    public function getTable(string $name, ?string $schema = null): TableReflection
    {
        $schema = ($schema ?: $this->currentSchema);

        if (isset($this->tables[$schema][$name])) {
            return $this->tables[$schema][$name];
        }
        $previous = $this->history->tables[$schema][$name] ?? null;
        if ($previous !== null) {
            if ($previous->wasDropped()) {
                throw new TableWasDroppedException($previous);
            } elseif ($previous->wasMoved()) {
                throw new TableWasMovedException($previous);
            }
        }

        $createTableCommand = $this->loader->getCreateTableCommand($name, $schema);
        $reflection = new TableReflection($this, new QualifiedName($name, $schema), $createTableCommand);
        $this->tables[$schema][$name] = $reflection;

        return $reflection;
    }

    public function findTable(string $name, ?string $schema = null): ?TableReflection
    {
        try {
            return $this->getTable($name, $schema);
        } catch (TableDoesNotExistException $e) {
            return null;
        }
    }

    /**
     * @param string|null $schema
     * @return \SqlFtw\Reflection\ViewReflection[]
     */
    public function getViews(?string $schema = null): array
    {
        if ($schema !== null) {
            $this->getSchema($schema);
            return $this->views[$schema];
        } else {
            return Arr::flatten($this->views);
        }
    }

    public function getView(string $name, ?string $schema = null): ViewReflection
    {
        $schema = ($schema ?: $this->currentSchema);

        if (isset($this->views[$schema][$name])) {
            return $this->views[$schema][$name];
        }
        $previous = $this->history->views[$schema][$name] ?? null;
        if ($previous !== null) {
            if ($previous->wasDropped()) {
                throw new ViewWasDroppedException($previous);
            }
        }

        $createViewCommand = $this->loader->getCreateViewCommand($name, $schema);
        $reflection = new ViewReflection(new QualifiedName($name, $schema), $createViewCommand);
        $this->views[$schema][$name] = $reflection;

        return $reflection;
    }

    public function findView(string $name, ?string $schema = null): ?ViewReflection
    {
        try {
            return $this->getView($name, $schema);
        } catch (ViewDoesNotExistException $e) {
            return null;
        }
    }

    /**
     * @param string|null $schema
     * @return \SqlFtw\Reflection\FunctionReflection[]
     */
    public function getFunctions(?string $schema = null): array
    {
        if ($schema !== null) {
            $this->getSchema($schema);
            return $this->functions[$schema];
        } else {
            return Arr::flatten($this->functions);
        }
    }

    public function getFunction(string $name, ?string $schema = null): FunctionReflection
    {
        $schema = ($schema ?: $this->currentSchema);

        if (isset($this->functions[$schema][$name])) {
            return $this->functions[$schema][$name];
        }
        $previous = $this->history->functions[$schema][$name] ?? null;
        if ($previous !== null) {
            if ($previous->wasDropped()) {
                throw new FunctionWasDroppedException($previous);
            }
        }

        $createFunctionCommand = $this->loader->getCreateFunctionCommand($name, $schema);
        $reflection = new FunctionReflection(new QualifiedName($name, $schema), $createFunctionCommand);
        $this->functions[$schema][$name] = $reflection;

        return $reflection;
    }

    public function findFunction(string $name, ?string $schema = null): ?FunctionReflection
    {
        try {
            return $this->getFunction($name, $schema);
        } catch (FunctionDoesNotExistException $e) {
            return null;
        }
    }

    /**
     * @param string|null $schema
     * @return \SqlFtw\Reflection\ProcedureReflection[]
     */
    public function getProcedures(?string $schema = null): array
    {
        if ($schema !== null) {
            $this->getSchema($schema);
            return $this->procedures[$schema];
        } else {
            return Arr::flatten($this->procedures);
        }
    }

    public function getProcedure(string $name, ?string $schema = null): ProcedureReflection
    {
        $schema = ($schema ?: $this->currentSchema);

        if (isset($this->procedures[$schema][$name])) {
            return $this->procedures[$schema][$name];
        }
        $previous = $this->history->procedures[$schema][$name] ?? null;
        if ($previous !== null) {
            if ($previous->wasDropped()) {
                throw new ProcedureWasDroppedException($previous);
            }
        }

        $createProcedureCommand = $this->loader->getCreateProcedureCommand($name, $schema);
        $reflection = new ProcedureReflection(new QualifiedName($name, $schema), $createProcedureCommand);
        $this->procedures[$schema][$name] = $reflection;

        return $reflection;
    }

    public function findProcedure(string $name, ?string $schema = null): ?ProcedureReflection
    {
        try {
            return $this->getProcedure($name, $schema);
        } catch (ProcedureDoesNotExistException $e) {
            return null;
        }
    }

    /**
     * @param string|null $schema
     * @return \SqlFtw\Reflection\TriggerReflection[]
     */
    public function getTriggers(?string $schema = null): array
    {
        if ($schema !== null) {
            $this->getSchema($schema);
            return $this->triggers[$schema];
        } else {
            return Arr::flatten($this->triggers);
        }
    }

    public function getTrigger(string $name, ?string $schema = null): TriggerReflection
    {
        $schema = ($schema ?: $this->currentSchema);

        if (isset($this->triggers[$schema][$name])) {
            return $this->triggers[$schema][$name];
        }
        $previous = $this->history->triggers[$schema][$name] ?? null;
        if ($previous !== null) {
            if ($previous->wasDropped()) {
                throw new TriggerWasDroppedException($previous);
            }
        }

        $createTriggerCommand = $this->loader->getCreateTriggerCommand($name, $schema);
        $reflection = new TriggerReflection(new QualifiedName($name, $schema), $createTriggerCommand);
        $this->triggers[$schema][$name] = $reflection;

        return $reflection;
    }

    public function findTrigger(string $name, ?string $schema = null): ?TriggerReflection
    {
        try {
            return $this->getTrigger($name, $schema);
        } catch (TriggerDoesNotExistException $e) {
            return null;
        }
    }

    /**
     * @param string|null $schema
     * @return \SqlFtw\Reflection\EventReflection[]
     */
    public function getEvents(?string $schema = null): array
    {
        if ($schema !== null) {
            $this->getSchema($schema);
            return $this->events[$schema];
        } else {
            return Arr::flatten($this->events);
        }
    }

    public function getEvent(string $name, ?string $schema = null): EventReflection
    {
        $schema = ($schema ?: $this->currentSchema);

        if (isset($this->events[$schema][$name])) {
            return $this->events[$schema][$name];
        }
        $previous = $this->history->events[$schema][$name] ?? null;
        if ($previous !== null) {
            if ($previous->wasDropped()) {
                throw new EventWasDroppedException($previous);
            }
        }

        $createEventCommand = $this->loader->getCreateEventCommand($name, $schema);
        $reflection = new EventReflection(new QualifiedName($name, $schema), $createEventCommand);
        $this->events[$schema][$name] = $reflection;

        return $reflection;
    }

    public function findEvent(string $name, ?string $schema = null): ?EventReflection
    {
        try {
            return $this->getEvent($name, $schema);
        } catch (EventDoesNotExistException $e) {
            return null;
        }
    }

}
