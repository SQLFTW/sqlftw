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
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Index\CreateIndexCommand;
use SqlFtw\Sql\Ddl\Index\DropIndexCommand;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddCheckAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddColumnsAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddConstraintAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddForeignKeyAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddIndexAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddPartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AlterCheckAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AlterColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AlterConstraintAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AlterIndexAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ChangeColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ConvertToCharsetAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DisableKeysAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DiscardTablespaceAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropCheckAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropConstraintAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropForeignKeyAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropIndexAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropPrimaryKeyAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\EnableKeysAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ImportTablespaceAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ModifyColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\OrderByAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\PartitioningAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RenameIndexAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RenameToAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ReorganizePartitionAction;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition;
use SqlFtw\Sql\Ddl\Table\Constraint\CheckDefinition;
use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition;
use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintType;
use SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use SqlFtw\Sql\Ddl\Table\DdlTablesCommand;
use SqlFtw\Sql\Ddl\Table\DropTableCommand;
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;
use SqlFtw\Sql\Ddl\Table\Option\TableOption;
use SqlFtw\Sql\Ddl\Table\Option\TableOptionsList;
use SqlFtw\Sql\Ddl\Table\Partition\PartitioningDefinition;
use SqlFtw\Sql\Ddl\Table\RenameTableCommand;
use SqlFtw\Sql\Ddl\Table\DdlTableCommand;
use SqlFtw\Sql\QualifiedName;
use function end;

class TableReflection
{
    use StrictBehaviorMixin;

    /** @var SchemaReflection */
    private $schema;

    /** @var bool */
    private $trackHistory;

    /** @var self|null */
    private $previous;

    /** @var DdlTableCommand|DdlTablesCommand */
    private $lastCommand;

    /** @var QualifiedName */
    private $name;

    /** @var ColumnReflection[] */
    private $columns = [];

    /**
     * All indexes including those created by columns (ADD COLUMN foo int PRIMARY) and constraints (ADD CONSTRAINT foo UNIQUE KEY ...).
     * Filter primary and unique keys from this array.
     * @var IndexReflection[]
     */
    private $indexes = [];

    /** @var ForeignKeyReflection[] */
    private $foreignKeys = [];

    /** @var CheckReflection[] */
    private $checks = [];

    /** @var TriggerReflection[] */
    private $triggers = [];

    /** @var TableOptionsList */
    private $options;

    /** @var PartitioningReflection|null */
    private $partitioning;

    // todo $tablespace

    // todo remove after todos:
    // phpcs:disable SlevomatCodingStandard.ControlStructures.JumpStatementsSpacing

    public function __construct(
        SchemaReflection $schema,
        CreateTableCommand $createTableCommand,
        bool $trackHistory = true
    ) {
        $this->schema = $schema;
        $this->trackHistory = $trackHistory;
        $this->lastCommand = $createTableCommand;
        $this->name = $createTableCommand->getName()->coalesce($schema->getName());

        foreach ($createTableCommand->getItems() as $item) {
            if ($item instanceof ColumnDefinition) {
                $this->addColumn($item);
            } elseif ($item instanceof IndexDefinition) {
                $this->addIndex($item);
            } elseif ($item instanceof ConstraintDefinition) {
                $constraintType = $item->getType();
                if ($constraintType->equals(ConstraintType::PRIMARY_KEY)) {
                    $this->addIndex($item->getIndexDefinition());
                } elseif ($constraintType->equals(ConstraintType::UNIQUE_KEY)) {
                    $this->addIndex($item->getIndexDefinition());
                } elseif ($constraintType->equals(ConstraintType::FOREIGN_KEY)) {
                    $this->addForeignKey($item->getForeignKeyDefinition(), $item->getName());
                } elseif ($constraintType->equals(ConstraintType::CHECK)) {
                    $this->addCheck($item->getCheckDefinition(), $item->getName());
                }
            } elseif ($item instanceof ForeignKeyDefinition) {
                $this->addForeignKey($item);
            } elseif ($item instanceof CheckDefinition) {
                $this->addCheck($item);
            }
        }

        $this->options = $createTableCommand->getOptions() ?? new TableOptionsList([]);
        $this->partitioning = new PartitioningReflection($this, $createTableCommand->getPartitioning());
    }

    public function apply(Command $command): self
    {
        if ($command instanceof AlterTableCommand) {
            return $this->alter($command);
        } elseif ($command instanceof DropTableCommand) {
            $that = clone $this;
            $that->lastCommand = $command;

            return $that;
        } elseif ($command instanceof RenameTableCommand) {
            // todo
        } elseif ($command instanceof CreateIndexCommand) {
            $that = clone $this;
            $that->addIndex($command->getIndex());
            $that->previous = $this;

            return $that;
        } elseif ($command instanceof DropIndexCommand) {

        } else {
            throw new ShouldNotHappenException('Unknown command.');
        }
    }

    private function alter(AlterTableCommand $alterTableCommand): self
    {
        $that = clone $this;
        $that->lastCommand[] = $alterTableCommand;

        foreach ($alterTableCommand->getActions()->getActions() as $action) {
            if ($action instanceof AddColumnAction) {
                $column = $action->getColumn();
                $that->addColumn($column);
            } elseif ($action instanceof AddColumnsAction) {
                foreach ($action->getColumns() as $column) {
                    $that->addColumn($column);
                }
            } elseif ($action instanceof AddIndexAction) {
                $that->addIndex($action->getIndex());
            } elseif ($action instanceof AddConstraintAction) {
                $constraint = $action->getConstraint();
                $constraintType = $constraint->getType();
                if ($constraintType->equals(ConstraintType::PRIMARY_KEY)) {
                    $that->addIndex($constraint->getIndexDefinition());
                    // MySQL ignores constraint name for indexes, no "constraint" is created, only index
                } elseif ($constraintType->equals(ConstraintType::UNIQUE_KEY)) {
                    $that->addIndex($constraint->getIndexDefinition());
                    // MySQL ignores constraint name for indexes, no "constraint" is created, only index
                } elseif ($constraintType->equals(ConstraintType::FOREIGN_KEY)) {
                    $that->addForeignKey($constraint->getForeignKeyDefinition(), $constraint->getName());
                    // MySQL ignores index name on foreign key, no index is created, only constraint
                } elseif ($constraintType->equals(ConstraintType::CHECK)) {
                    $that->addCheck($constraint->getCheckDefinition(), $constraint->getName());
                }
            } elseif ($action instanceof AddForeignKeyAction) {
                $that->addForeignKey($action->getForeignKey());
                // MySQL ignores index name on foreign key, no index is created
            } elseif ($action instanceof AddCheckAction) {
                $that->addCheck($action->getCheck());
            } elseif ($action instanceof AddPartitionAction) {
                // todo
                continue;
            } elseif ($action instanceof AlterCheckAction) {
                $name = $action->getName();
                $check = $this->getCheck($name);
                $checkDefinition = $check->getCheckDefinition()->duplicateWithEnforced($action->isEnforced());
                $that->checks[$name] = new CheckReflection($that, $checkDefinition);
            } elseif ($action instanceof AlterConstraintAction) {
                $name = $action->getName();
                $check = $this->getCheck($name);
                $checkDefinition = $check->getCheckDefinition()->duplicateWithEnforced($action->isEnforced());
                $that->checks[$name] = new CheckReflection($that, $checkDefinition);
            } elseif ($action instanceof AlterColumnAction) {
                $name = $action->getName();
                $column = $this->getColumn($name);
                $columnDefinition = $column->getColumnDefinition()->duplicateWithDefaultValue($action->getDefault());
                $that->columns[$name] = new ColumnReflection($that, $columnDefinition);
            } elseif ($action instanceof AlterIndexAction) {
                $name = $action->getName();
                $index = $that->getIndex($name);
                $indexDefinition = $index->getIndexDefinition()->duplicateWithVisibility($action->visible());
                $that->indexes[$name] = new IndexReflection($that, $indexDefinition);
            } elseif ($action instanceof ChangeColumnAction) {
                $name = $action->getOldName();
                $that->getColumn($name);
                $that->columns[$action->getColumn()->getName()] = new ColumnReflection($that, $action->getColumn());
                // todo after, first?
            } elseif ($action instanceof ConvertToCharsetAction) {
                // todo
                continue;
            } elseif ($action instanceof ModifyColumnAction) {
                $name = $action->getColumn()->getName();
                $that->getColumn($name);
                $that->columns[$name] = new ColumnReflection($that, $action->getColumn());
                // todo after, first?
            } elseif ($action instanceof RenameIndexAction) {
                $name = $action->getOldName();
                $newName = $action->getNewName();
                $index = $that->removeIndex($name);
                $that->addIndex($index->getIndexDefinition()->duplicateWithNewName($newName));
            } elseif ($action instanceof ReorganizePartitionAction) {
                // todo
                continue;
            } elseif ($action instanceof DropCheckAction) {
                $this->removeCheck($action->getName());
            } elseif ($action instanceof DropConstraintAction) {
                $name = $action->getName();
                if (isset($this->foreignKeys[$name])) {
                    $this->removeForeignKey($name);
                } elseif (isset($this->checks[$name])) {
                    $this->removeCheck($name);
                } else {
                    throw new CheckDoesNotExistException($name, $this->name);
                }
            } elseif ($action instanceof DropColumnAction) {
                $this->removeColumn($action->getName());
            } elseif ($action instanceof DropIndexAction) {
                $that->removeIndex($action->getName());
            } elseif ($action instanceof DropForeignKeyAction) {
                $this->removeForeignKey($action->getName());
            } elseif ($action instanceof DropPrimaryKeyAction) {
                $this->removeIndex(IndexDefinition::PRIMARY_KEY_NAME);
            } elseif ($action instanceof OrderByAction) {
                // ignore (MyISAM only)
            } elseif ($action instanceof RenameToAction) {
                // todo
            } elseif ($action instanceof EnableKeysAction || $action instanceof DisableKeysAction) {
                // pass
            } elseif ($action instanceof DiscardTablespaceAction) {
                // todo
            } elseif ($action instanceof ImportTablespaceAction) {
                // todo
            } elseif ($action instanceof PartitioningAction) {
                $newReflection = $this->partitioning->apply($action);
                if ($newReflection !== $this->partitioning) {
                    // todo
                }
            } else {
                throw new ShouldNotHappenException('Unknown action.');
            }
        }

        $options = $alterTableCommand->getTableOptions();
        if ($options !== null) {
            $that->options = $that->updateOptions($that->options, $options->getOptions());
        }

        return $that;
    }

    public function rename(RenameTableCommand $renameTableCommand): self
    {
        $that = clone $this;
        $that->lastCommand[] = $renameTableCommand;
        $that->name = $renameTableCommand->getNewNameForTable($this->name);

        return $that;
    }

    /**
     * @param RenameTableCommand|AlterTableCommand $tableCommand
     * @return TableReflection
     */
    public function moveByRenaming($tableCommand): self
    {
        $that = clone $this;
        $that->lastCommand[] = $tableCommand;
        $that->columns = $that->indexes = $that->foreignKeys = [];
        $that->options = new TableOptionsList([]);
        $that->partitioning = null;

        return $that;
    }

    public function drop(DropTableCommand $dropTableCommand): self
    {
        $that = clone $this;
        $that->lastCommand[] = $dropTableCommand;
        $that->columns = $that->indexes = $that->foreignKeys = [];
        $that->options = new TableOptionsList([]);
        $that->partitioning = null;

        return $that;
    }

    public function createIndex(CreateIndexCommand $createIndexCommand): self
    {
        $that = clone $this;
        $that->lastCommand[] = $createIndexCommand;
        $that->addIndex($createIndexCommand->getIndex());

        return $that;
    }

    public function dropIndex(DropIndexCommand $dropIndexCommand): self
    {
        $that = clone $this;
        $that->lastCommand[] = $dropIndexCommand;
        $name = $dropIndexCommand->getName();
        $that->removeIndex($name->getName());

        return $that;
    }

    public function createTrigger(TriggerReflection $trigger): self
    {
        $that = clone $this;
        $name = $trigger->getName();
        $oldTrigger = $this->findTrigger($name->getName());
        if ($oldTrigger !== null) {
            throw new TriggerAlreadyExistsException($name);
        }
        $that->triggers[$trigger->getName()->getName()] = $trigger;

        return $that;
    }

    public function dropTrigger(string $name): self
    {
        $that = clone $this;
        $this->getTrigger($name);
        unset($that->triggers[$name]);

        return $that;
    }

    // internal setters ------------------------------------------------------------------------------------------------

    // phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod

    private function addColumn(ColumnDefinition $column): void
    {
        $name = $column->getName();
        $currentColumn = $this->findColumn($name);
        if ($currentColumn !== null) {
            throw new ColumnAlreadyExistsException($name, $this->name);
        }
        $columnReflection = new ColumnReflection($this, $column);
        $this->columns[$column->getName()] = $columnReflection;

        $indexType = $column->getIndexType();
        if ($indexType !== null) {
            $this->addIndex(new IndexDefinition(null, $indexType, [$column->getName()]));
        }

        $reference = $column->getReference();
        if ($reference !== null) {
            // todo: should also add an index?
            $foreignKey = new ForeignKeyDefinition([$column->getName()], $reference);
            $this->addForeignKey($foreignKey, null, $columnReflection);
        }

        $check = $column->getCheck();
        if ($check !== null) {
            $this->addCheck($check, null, $columnReflection);
        }
    }

    private function removeColumn(string $name): ColumnReflection
    {
        $column = $this->getColumn($name);
        unset($this->columns[$name]);

        return $column;
    }

    private function addIndex(IndexDefinition $index, ?ColumnReflection $column = null): void
    {
        $name = $index->getName();
        if ($name === null) {
            $columns = $index->getColumnNames();
            $name = $this->schema->getPlatform()->getNamingStrategy()->createIndexName($this, $columns);
        } else {
            $currentIndex = $this->findIndex($name);
            if ($currentIndex !== null) {
                throw new IndexAlreadyExistsException($name, $this->name);
            }
        }

        $this->indexes[$name] = new IndexReflection($this, $index, $column);
    }

    private function removeIndex(?string $name): IndexReflection
    {
        $index = $this->getIndex($name);
        unset($this->indexes[$name]);

        // todo remove associated constraint

        return $index;
    }

    private function addForeignKey(ForeignKeyDefinition $foreignKey, ?string $name = null, ?ColumnReflection $column = null): void
    {
        if ($name === null) {
            $name = $this->schema->getPlatform()->getNamingStrategy()->createForeignKeyName($this, $foreignKey->getColumns());
        } else {
            $currentForeignKey = $this->findForeignKey($name);
            if ($currentForeignKey !== null) {
                throw new ForeignKeyAlreadyExistsException($name, $this->name);
            }
        }

        $this->foreignKeys[$name] = new ForeignKeyReflection($this, $foreignKey, $column);
    }

    private function removeForeignKey(string $name): ForeignKeyReflection
    {
        $foreignKey = $this->getForeignKey($name);
        unset($this->foreignKeys[$name]);

        return $foreignKey;
    }

    private function addCheck(CheckDefinition $check, ?string $name = null, ?ColumnReflection $column = null): void
    {
        if ($name === null) {
            $name = $this->schema->getPlatform()->getNamingStrategy()->createCheckName($this, []);
        } else {
            $currentCheck = $this->findCheck($name);
            if ($currentCheck !== null) {
                throw new CheckAlreadyExistsException($name, $this->name);
            }
        }

        $this->checks[$name] = new CheckReflection($this, $check, $column);
    }

    private function removeCheck(string $name): CheckReflection
    {
        $check = $this->getCheck($name);
        unset($this->checks[$name]);

        return $check;
    }

    /**
     * @param TableOptionsList $old
     * @param TableOption[] $newOptions
     * @return TableOptionsList
     */
    private function updateOptions(TableOptionsList $old, array $newOptions): TableOptionsList
    {
        $options = $old->getOptions();
        foreach ($newOptions as $name => $value) {
            $options[$name] = $value;
        }

        return new TableOptionsList($options);
    }

    private function updatePartitioning(PartitioningDefinition $partitioning): PartitioningDefinition
    {
        // todo
    }

    // phpcs:enable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod

    // getters ---------------------------------------------------------------------------------------------------------

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function wasDropped(): bool
    {
        return end($this->lastCommand) instanceof DropTableCommand;
    }

    public function wasRenamed(): bool
    {
        $command = end($this->lastCommand);

        return $command instanceof RenameTableCommand
            || ($command instanceof AlterTableCommand && $command->getActions()->filter(RenameToAction::class) !== []);
    }

    public function getLastCommand(): Command
    {
        return end($this->lastCommand);
    }

    /**
     * @return DdlTableCommand[]
     */
    public function getCommands(): array
    {
        return $this->lastCommand;
    }

    /**
     * @return ColumnReflection[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumn(string $name): ColumnReflection
    {
        $column = $this->columns[$name] ?? null;
        if ($column === null) {
            throw new ColumnNotFoundException($name, $this->name);
        }
        // todo: moved / removed

        return $column;
    }

    public function findColumn(string $name): ?ColumnReflection
    {
        return $this->columns[$name] ?? null;
    }

    /**
     * @return IndexReflection[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getIndex(?string $name): IndexReflection
    {
        $index = $this->indexes[$name] ?? null;
        if ($index === null) {
            throw new IndexDoesNotExistException($name, $this->name->getName());
        }

        return $index;
    }

    public function findIndex(string $name): ?IndexReflection
    {
        return $this->indexes[$name] ?? null;
    }

    /**
     * @return ForeignKeyReflection[]
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function getForeignKey(string $name): ForeignKeyReflection
    {
        $foreignKey = $this->foreignKeys[$name] ?? null;
        if ($foreignKey === null) {
            throw new ForeignKeyDoesNotExistException($name, $this->name->getName(), $this->name->getSchema());
        }

        return $foreignKey;
    }

    public function findForeignKey(string $name): ?ForeignKeyReflection
    {
        return $this->foreignKeys[$name] ?? null;
    }

    /**
     * @return CheckReflection[]
     */
    public function getChecks(): array
    {
        return $this->checks;
    }

    public function getCheck(string $name): CheckReflection
    {
        $check = $this->checks[$name] ?? null;
        if ($check === null) {
            throw new CheckDoesNotExistException($name, $this->name->getName(), $this->name->getSchema());
        }

        return $check;
    }

    public function findCheck(string $name): ?CheckReflection
    {
        return $this->checks[$name] ?? null;
    }

    /**
     * @return TriggerReflection[]
     */
    public function getTriggers(): array
    {
        return $this->triggers;
    }

    public function getTrigger(string $name): TriggerReflection
    {
        $trigger = $this->triggers[$name] ?? null;
        if ($trigger === null) {
            throw new TriggerDoesNotExistException($name, $this->name->getSchema());
        }

        return $trigger;
    }

    public function findTrigger(string $name): ?TriggerReflection
    {
        return $this->triggers[$name] ?? null;
    }

    public function getOptions(): TableOptionsList
    {
        return $this->options;
    }

    public function getPartitioning(): ?PartitioningReflection
    {
        return $this->partitioning;
    }

}
