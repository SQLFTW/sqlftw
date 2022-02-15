<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use SqlFtw\Platform\Platform;
use SqlFtw\Reflection\Loader\ReflectionLoader;
use SqlFtw\Sql\ColumnName;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\Utility\UseCommand;
use SqlFtw\Sql\QualifiedName;
use function end;

class ReflectionProvider
{

    /** @var Platform */
    private $platform;

    /** @var ReflectionLoader */
    private $loader;

    /** @var Session */
    private $session;

    /** @var DatabaseReflection[] */
    private $history = [];

    public function __construct(
        Platform $platform,
        ReflectionLoader $loader
    )
    {
        $this->platform = $platform;
        $this->loader = $loader;
        $this->session = new Session($this);
        $this->history[] = new DatabaseReflection($this->session);
    }

    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function getFirst(): DatabaseReflection
    {
        return $this->history[0];
    }

    public function getLast(): DatabaseReflection
    {
        return end($this->history);
    }

    public function apply(Command $command): void
    {
        if ($command instanceof UseCommand) {
            $schema = $command->getSchema();
            $this->getSchema($schema);
            $this->session->changeSchema($schema);

        } else {
            $last = end($this->history);
            $next = $last->apply($command);
            if ($last !== $next) {
                $this->history[] = $next;
            }
        }
    }

    // exists ----------------------------------------------------------------------------------------------------------

    public function schemaExists(?string $name): bool
    {
        $name = $name ?? $this->session->getSchema();

        if ($this->findSchema($name) !== null) {
            return true;
        }

        return (bool) $this->loadSchema($name);
    }

    public function tablespaceExists(?string $name): bool
    {
        $name = $name ?? $this->session->getSchema();

        if ($this->findTablespace($name) !== null) {
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

        if ($this->findTable($name)) {
            return true;
        }

        return (bool) $this->loadTable($name->coalesce($this->session->getSchema()), $schema);
    }

    public function columnExists(ColumnName $name): bool
    {
        $schema = $this->findSchema($name->getSchema());
        if ($schema === null) {
            return false;
        }

        $table = $schema->getTableIfLoaded($name->getTable());
        if ($table === null) {
            $table = $this->loadTable($name->getTableName()->coalesce($this->session->getSchema()), $schema);
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

        return $this->loadView($name->coalesce($this->session->getSchema()), $schema) !== null;
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

        return $this->loadFunction($name->coalesce($this->session->getSchema()), $schema) !== null;
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

        return $this->loadProcedure($name->coalesce($this->session->getSchema()), $schema) !== null;
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

        return $this->loadEvent($name->coalesce($this->session->getSchema()), $schema) !== null;
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

        return $this->loadTrigger($name->coalesce($this->session->getSchema()), $schema) !== null;
    }

    // find ------------------------------------------------------------------------------------------------------------

    public function findSchema(?string $name): ?SchemaReflection
    {
        $name = $name ?? $this->session->getSchema();

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

    public function getSchema(?string $name, int $before = 0): SchemaReflection
    {
        $name = $name ?? $this->session->getSchema();
        $database = $this->database;

        // skip history
        for ($n = 0; $n < $before; $n++) {
            $database = $database->getPrevious();
            if ($database === null) {
                break;
            }
        }

        // search history
        while ($database !== null) {
            $schema = $database->getSchema($name);
            if ($schema === null) {
                $database = $database->getPrevious();
            } elseif ($schema->wasDropped()) {
                throw new SchemaDroppedException($name, $schema->getLastCommand());
            } elseif ($schema->wasRenamed($name)) {
                throw new SchemaRenamedException($name, $schema->getLastCommand());
            } else {
                return $schema;
            }
        }

        // try to load
        $schema = $this->loadSchema($name);
        if ($schema === null) {
            throw new SchemaNotFoundException($name);
        }

        return $schema;
    }

    public function getTablespace(?string $name): TablespaceReflection
    {
        $name = $name ?? $this->session->getSchema();

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
            } elseif ($table->wasRenamed($name->getName())) {
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

        $reflection = new SchemaReflection($command);
        $this->schemas[$name] = $reflection;
        // todo: history

        return $reflection;
    }

    private function loadTablespace(string $name): ?TablespaceReflection
    {
        $command = $this->loader->getCreateTablespaceCommand($name);
        if ($command === null) {
            return null;
        }

        $reflection = new TablespaceReflection($command);
        $this->tablespaces[$name] = $reflection;
        // todo: history

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
        // todo: history

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
        // todo: history

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
        // todo: history

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
        // todo: history

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
        // todo: history

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
        // todo: history

        return $schema->getTriggerIfLoaded($name->getName());
    }

    /**
     * @return mixed[]
     */
    public function loadVariables(): array
    {

    }

    /**
     * @return mixed[]
     */
    public function loadUserVariables(): array
    {

    }

}