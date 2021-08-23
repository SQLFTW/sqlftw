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
use SqlFtw\Platform\Platform;
use SqlFtw\Reflection\Loader\ReflectionLoader;
use SqlFtw\Sql\ColumnName;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Schema\AlterSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\CreateSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\DropSchemaCommand;
use SqlFtw\Sql\Ddl\SchemaObjectCommand;
use SqlFtw\Sql\Ddl\SchemaObjectsCommand;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AlterTableAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RenameToAction;
use SqlFtw\Sql\Ddl\Table\RenameTableCommand;
use SqlFtw\Sql\Ddl\Tablespace\AlterTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\CreateTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\DropTablespaceCommand;
use SqlFtw\Sql\Dml\Utility\UseCommand;
use SqlFtw\Sql\QualifiedName;

/**
 * Represents whole "database" or server layer, not just a single schema.
 *
 * DatabaseReflection is mutable and can change even with calling get... methods as it lazy loads database objects.
 * All reflection objects under this level are immutable (SchemaReflection, TableReflection etc.).
 * You can get snapshot of database structure at any point by cloning DatabaseReflection or iterating through
 *   $history when history is enabled.
 */
class DatabaseReflection
{
    use StrictBehaviorMixin;

    /** @var Platform */
    private $platform;

    /** @var ReflectionLoader */
    private $loader;

    /** @var bool */
    private $trackHistory;

    /** @var self|null */
    private $previous;

    /** @var string */
    private $currentSchema;

    /** @var SchemaReflection[] */
    private $schemas = [];

    /** @var TablespaceReflection[] */
    private $tablespaces = [];

    /** @var VariablesReflection[] */
    private $variables = [];

    public function __construct(
        Platform $platform,
        ReflectionLoader $loader,
        string $currentSchema,
        bool $trackHistory = true
    ) {
        $this->platform = $platform;
        $this->loader = $loader;
        $this->currentSchema = $currentSchema;
        $this->trackHistory = $trackHistory;
    }

    public function apply(Command $command): void
    {
        if ($command instanceof UseCommand) {
            $schema = $command->getSchema();
            $this->getSchema($schema);
            $this->currentSchema = $schema;

        } elseif ($command instanceof CreateSchemaCommand) {
            $schema = $command->getName();
            $oldReflection = $this->findSchema($schema);
            if ($oldReflection !== null) {
                throw new SchemaAlreadyExistsException($schema);
            }

            $newReflection = new SchemaReflection($this, $command, $this->trackHistory);
            if ($this->trackHistory) {
                $this->previous = clone $this;
            }
            $this->schemas[$schema] = $newReflection;

        } elseif ($command instanceof AlterSchemaCommand) {
            $schema = $command->getName();

            $oldReflection = $this->getSchema($schema);
            if ($this->trackHistory) {
                $this->previous = clone $this;
            }
            $this->schemas[$schema] = $oldReflection->apply($command);

        } elseif ($command instanceof DropSchemaCommand) {
            $schema = $command->getName();

            if ($this->trackHistory) {
                $oldReflection = $this->getSchema($schema);
                $this->previous = clone $this;
                $this->previous->schemas[$schema] = $oldReflection->apply($command);
            }

            unset($this->schemas[$schema]);

        } elseif ($command instanceof CreateTablespaceCommand) {
            $tablespace = $command->getName();
            $oldReflection = $this->findTablespace($tablespace);
            if ($oldReflection !== null) {
                throw new TablespaceAlreadyExistsException($tablespace);
            }

            $newReflection = new TablespaceReflection($this, $command, $this->trackHistory);
            if ($this->trackHistory) {
                $this->previous = clone $this;
            }
            $this->tablespaces[$tablespace] = $newReflection;

        } elseif ($command instanceof AlterTablespaceCommand) {
            $tablespace = $command->getName();

            $oldReflection = $this->getTablespace($tablespace);
            if ($this->trackHistory) {
                $this->previous = clone $this;
            }
            $this->tablespaces[$tablespace] = $oldReflection->apply($command);

        } elseif ($command instanceof DropTablespaceCommand) {
            $tablespace = $command->getName();

            if ($this->trackHistory) {
                $oldReflection = $this->getTablespace($tablespace);
                $this->previous = clone $this;
                $this->previous->tablespaces[$tablespace] = $oldReflection->apply($command);
            }

            unset($this->tablespaces[$tablespace]);
        } elseif ($command instanceof SchemaObjectCommand) {
            $schema = $command->getName()->getSchema() ?: $this->currentSchema;
            $oldReflection = $this->getSchema($schema);
            $newReflection = $oldReflection->apply($command);

            if ($this->trackHistory && $oldReflection !== $newReflection) {
                $this->previous = clone $this;
            }
            $this->schemas[$schema] = $newReflection;

            /** @var RenameToAction $renameAction */
            if ($command instanceof AlterTableAction && $renameAction = $command->getRenameAction() !== null) {
                $targetSchema = $renameAction->getNewName()->getSchema() ?? $this->currentSchema;
                $oldReflection = $this->getSchema($targetSchema);
                $newReflection = $oldReflection->apply($command);

                $this->schemas[$targetSchema] = $newReflection;
            }

        } elseif ($command instanceof SchemaObjectsCommand) {
            $previous = clone $this;
            $schemas = QualifiedName::uniqueSchemas($command->getNames(), $this->currentSchema);
            foreach ($schemas as $schema) {
                $oldReflection = $this->getSchema($schema);
                $newReflection = $oldReflection->apply($command);

                if ($this->trackHistory && $oldReflection !== $newReflection) {
                    $this->previous = $previous;
                }
                $this->schemas[$schema] = $newReflection;
            }
            if ($command instanceof RenameTableCommand) {
                $targetSchemas = QualifiedName::uniqueSchemas($command->getNewNames(), $this->currentSchema);
                foreach ($targetSchemas as $schema) {
                    if (in_array($schema, $schemas, true)) {
                        // already updated in previous foreach
                        continue;
                    }
                    $oldReflection = $this->getSchema($schema);
                    $newReflection = $oldReflection->apply($command);

                    $this->schemas[$schema] = $newReflection;
                }
            }
        }
    }

    public function getPrevious(): ?self
    {
        return $this->previous;
    }

    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    // exists ----------------------------------------------------------------------------------------------------------

    public function schemaExists(?string $name): bool
    {
        $name = $name ?? $this->currentSchema;

        if (isset($this->schemas[$name])) {
            return true;
        }

        return (bool) $this->loadSchema($name);
    }

    public function tablespaceExists(?string $name): bool
    {
        $name = $name ?? $this->currentSchema;

        if (isset($this->tablespaces[$name])) {
            return true;
        }

        return (bool) $this->loadTablespace($name);
    }

    public function tableExists(QualifiedName $name): bool
    {
        $schema = $this->findSchema($name->getSchema());
        if ($schema === null) {
            return false;
        }

        if ($schema->getTableIfLoaded($name->getName())) {
            return true;
        }

        return $this->loadTable($name->coalesce($this->currentSchema), $schema) !== null;
    }

    public function columnExists(ColumnName $name): bool
    {
        $schema = $this->findSchema($name->getSchema());
        if ($schema === null) {
            return false;
        }

        $table = $schema->getTableIfLoaded($name->getTable());
        if ($table === null) {
            $table = $this->loadTable($name->getTableName()->coalesce($this->currentSchema), $schema);
        }
        if ($table === null) {
            return false;
        }

        return (bool) $table->findColumn($name->getName());
    }

    public function viewExists(QualifiedName $name): bool
    {
        $schema = $this->findSchema($name->getSchema());
        if ($schema === null) {
            return false;
        }

        if ($schema->getViewIfLoaded($name->getName())) {
            return true;
        }

        return $this->loadView($name->coalesce($this->currentSchema), $schema) !== null;
    }

    public function functionExists(QualifiedName $name): bool
    {
        $schema = $this->findSchema($name->getSchema());
        if ($schema === null) {
            return false;
        }

        if ($schema->getFunctionIfLoaded($name->getName())) {
            return true;
        }

        return $this->loadFunction($name->coalesce($this->currentSchema), $schema) !== null;
    }

    public function procedureExists(QualifiedName $name): bool
    {
        $schema = $this->findSchema($name->getSchema());
        if ($schema === null) {
            return false;
        }

        if ($schema->getProcedureIfLoaded($name->getName())) {
            return true;
        }

        return $this->loadProcedure($name->coalesce($this->currentSchema), $schema) !== null;
    }

    public function eventExists(QualifiedName $name): bool
    {
        $schema = $this->findSchema($name->getSchema());
        if ($schema === null) {
            return false;
        }

        if ($schema->getEventIfLoaded($name->getName())) {
            return true;
        }

        return $this->loadEvent($name->coalesce($this->currentSchema), $schema) !== null;
    }

    public function triggerExists(QualifiedName $name): bool
    {
        $schema = $this->findSchema($name->getSchema());
        if ($schema === null) {
            return false;
        }

        if ($schema->getTriggerIfLoaded($name->getName())) {
            return true;
        }

        return $this->loadTrigger($name->coalesce($this->currentSchema), $schema) !== null;
    }

    // find ------------------------------------------------------------------------------------------------------------

    public function findSchema(?string $name): ?SchemaReflection
    {
        $name = $name ?? $this->currentSchema;

        return $this->schemas[$name] ?? $this->loadSchema($name);
    }

    public function findTablespace(string $name): ?TablespaceReflection
    {
        return $this->tablespaces[$name] ?? $this->loadTablespace($name);
    }

    public function findTable(QualifiedName $name): ?TableReflection
    {
        $schema = $this->findSchema($name->getSchema());
        if ($schema === null) {
            return null;
        }

        // todo ?
    }

    // get -------------------------------------------------------------------------------------------------------------

    public function getSchema(?string $name): SchemaReflection
    {
        $name = $name ?? $this->currentSchema;

        $database = $this;
        while ($database !== null) {
            $schema = $database->schemas[$name] ?? null;
            if ($schema === null) {
                $database = $database->previous;
            } elseif ($schema->wasDropped()) {
                throw new SchemaDroppedException($name, $schema->getLastCommand());
            } elseif ($schema->wasRenamed()) {
                throw new SchemaRenamedException($name, $schema->getLastCommand());
            } else {
                return $schema;
            }
        }

        $schema = $this->loadSchema($name);
        if ($schema === null) {
            throw new SchemaNotFoundException($name);
        }

        return $schema;
    }

    public function getTablespace(?string $name): TablespaceReflection
    {
        $name = $name ?? $this->currentSchema;

        $database = $this;
        while ($database !== null) {
            $tablespace = $database->tablespaces[$name] ?? null;
            if ($tablespace === null) {
                $database = $database->previous;
            } elseif ($tablespace->wasDropped()) {
                throw new TablespaceDroppedException($name, $tablespace->getLastCommand());
            } elseif ($tablespace->wasRenamed()) {
                throw new TablespaceRenamedException($name, $tablespace->getLastCommand());
            } else {
                return $tablespace;
            }
        }

        $tablespace = $this->loadTablespace($name);
        if ($tablespace === null) {
            throw new TablespaceNotFoundException($name);
        }

        return $tablespace;
    }

    public function getTable(QualifiedName $name): TableReflection
    {
        $schema = $current = $this->getSchema($name->getSchema());
        while ($schema !== null) {
            $table = $schema->getTableIfLoaded($name->getName());
            if ($table === null) {
                $schema = $schema->getPrevious();
            } elseif ($table->wasDropped()) {
                throw new TableDroppedException($name, $table->getLastCommand());
            } elseif ($table->wasRenamed()) {
                throw new TableRenamedException($name, $table->getLastCommand());
            } else {
                return $table;
            }
        }

        $table = $this->loadTable($name, $current);
        if ($table === null) {
            throw new TableNotFoundException($name);
        }

        return $table;
    }

    public function getView(QualifiedName $name): ViewReflection
    {
        $schema = $current = $this->getSchema($name->getSchema());
        while ($schema !== null) {
            $view = $schema->getViewIfLoaded($name->getName());
            if ($view === null) {
                $schema = $schema->getPrevious();
            } elseif ($view->wasDropped()) {
                throw new ViewDroppedException($name, $view->getLastCommand());
            } elseif ($view->wasRenamed()) {
                throw new ViewRenamedException($name, $view->getLastCommand());
            } else {
                return $view;
            }
        }

        $view = $this->loadView($name, $current);
        if ($view === null) {
            throw new ViewNotFoundException($name);
        }

        return $view;
    }

    public function getFunction(QualifiedName $name): FunctionReflection
    {
        $schema = $current = $this->getSchema($name->getSchema());
        while ($schema !== null) {
            $function = $schema->getFunctionIfLoaded($name->getName());
            if ($function === null) {
                $schema = $schema->getPrevious();
            } elseif ($function->wasDropped()) {
                throw new FunctionDroppedException($name, $function->getLastCommand());
            } elseif ($function->wasRenamed()) {
                throw new FunctionRenamedException($name, $function->getLastCommand());
            } else {
                return $function;
            }
        }

        $function = $this->loadFunction($name, $current);
        if ($function === null) {
            throw new FunctionNotFoundException($name);
        }

        return $function;
    }

    public function getProcedure(QualifiedName $name): ProcedureReflection
    {
        $schema = $current = $this->getSchema($name->getSchema());
        while ($schema !== null) {
            $procedure = $schema->getProcedureIfLoaded($name->getName());
            if ($procedure === null) {
                $schema = $schema->getPrevious();
            } elseif ($procedure->wasDropped()) {
                throw new ProcedureDroppedException($name, $procedure->getLastCommand());
            } elseif ($procedure->wasRenamed()) {
                throw new ProcedureRenamedException($name, $procedure->getLastCommand());
            } else {
                return $procedure;
            }
        }

        $procedure = $this->loadProcedure($name, $current);
        if ($procedure === null) {
            throw new ProcedureNotFoundException($name);
        }

        return $procedure;
    }

    public function getEvent(QualifiedName $name): EventReflection
    {
        $schema = $current = $this->getSchema($name->getSchema());
        while ($schema !== null) {
            $event = $schema->getEventIfLoaded($name->getName());
            if ($event === null) {
                $schema = $schema->getPrevious();
            } elseif ($event->wasDropped()) {
                throw new EventDroppedException($name, $event->getLastCommand());
            } elseif ($event->wasRenamed()) {
                throw new EventRenamedException($name, $event->getLastCommand());
            } else {
                return $event;
            }
        }

        $event = $this->loadEvent($name, $current);
        if ($event === null) {
            throw new EventNotFoundException($name);
        }

        return $event;
    }

    public function getTrigger(QualifiedName $name): TriggerReflection
    {
        $schema = $current = $this->getSchema($name->getSchema());
        while ($schema !== null) {
            $trigger = $schema->getTriggerIfLoaded($name->getName());
            if ($trigger === null) {
                $schema = $schema->getPrevious();
            } elseif ($trigger->wasDropped()) {
                throw new TriggerDroppedException($name, $trigger->getLastCommand());
            } elseif ($trigger->wasRenamed()) {
                throw new TriggerRenamedException($name, $trigger->getLastCommand());
            } else {
                return $trigger;
            }
        }

        $trigger = $this->loadTrigger($name, $current);
        if ($trigger === null) {
            throw new TriggerNotFoundException($name);
        }

        return $trigger;
    }

    // loaders ---------------------------------------------------------------------------------------------------------

    private function loadSchema(string $name): ?SchemaReflection
    {
        $command = $this->loader->getCreateSchemaCommand($name);
        if ($command === null) {
            return null;
        }

        $reflection = new SchemaReflection($this, $command, $this->trackHistory);
        $this->schemas[$name] = $reflection;

        return $reflection;
    }

    private function loadTablespace(string $name): ?TablespaceReflection
    {
        $command = $this->loader->getCreateTablespaceCommand($name);
        if ($command === null) {
            return null;
        }

        $reflection = new TablespaceReflection($this, $command, $this->trackHistory);
        $this->tablespaces[$name] = $reflection;

        return $reflection;
    }

    private function loadTable(QualifiedName $name, SchemaReflection $schema): ?TableReflection
    {
        $command = $this->loader->getCreateTableCommand($name);
        if ($command === null) {
            return null;
        }

        $newReflection = $schema->apply($command);
        $this->schemas[$name->getSchema()] = $newReflection;

        return $schema->getTableIfLoaded($name->getName());
    }

    private function loadView(QualifiedName $name, SchemaReflection $schema): ?ViewReflection
    {
        $command = $this->loader->getCreateViewCommand($name);
        if ($command === null) {
            return null;
        }

        $newReflection = $schema->apply($command);
        $this->schemas[$name->getSchema()] = $newReflection;

        return $schema->getViewIfLoaded($name->getName());
    }

    private function loadFunction(QualifiedName $name, SchemaReflection $schema): ?FunctionReflection
    {
        $command = $this->loader->getCreateFunctionCommand($name);
        if ($command === null) {
            return null;
        }

        $newReflection = $schema->apply($command);
        $this->schemas[$name->getSchema()] = $newReflection;

        return $schema->getFunctionIfLoaded($name->getName());
    }

    private function loadProcedure(QualifiedName $name, SchemaReflection $schema): ?ProcedureReflection
    {
        $command = $this->loader->getCreateFunctionCommand($name);
        if ($command === null) {
            return null;
        }

        $newReflection = $schema->apply($command);
        $this->schemas[$name->getSchema()] = $newReflection;

        return $schema->getProcedureIfLoaded($name->getName());
    }

    private function loadEvent(QualifiedName $name, SchemaReflection $schema): ?EventReflection
    {
        $command = $this->loader->getCreateFunctionCommand($name);
        if ($command === null) {
            return null;
        }

        $newReflection = $schema->apply($command);
        $this->schemas[$name->getSchema()] = $newReflection;

        return $schema->getEventIfLoaded($name->getName());
    }

    private function loadTrigger(QualifiedName $name, SchemaReflection $schema): ?TriggerReflection
    {
        $command = $this->loader->getCreateFunctionCommand($name);
        if ($command === null) {
            return null;
        }

        $newReflection = $schema->apply($command);
        $this->schemas[$name->getSchema()] = $newReflection;

        return $schema->getTriggerIfLoaded($name->getName());
    }

}
