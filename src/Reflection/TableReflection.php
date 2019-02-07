<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\NotImplementedException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Sql\Ddl\Index\CreateIndexCommand;
use SqlFtw\Sql\Ddl\Index\DropIndexCommand;
use SqlFtw\Sql\Ddl\Table\Alter\AddColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\AddColumnsAction;
use SqlFtw\Sql\Ddl\Table\Alter\AddConstraintAction;
use SqlFtw\Sql\Ddl\Table\Alter\AddForeignKeyAction;
use SqlFtw\Sql\Ddl\Table\Alter\AddIndexAction;
use SqlFtw\Sql\Ddl\Table\Alter\AddPartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\AlterColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\AlterIndexAction;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableActionType;
use SqlFtw\Sql\Ddl\Table\Alter\ChangeColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\ConvertToCharsetAction;
use SqlFtw\Sql\Ddl\Table\Alter\ExchangePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\ModifyColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\RenameIndexAction;
use SqlFtw\Sql\Ddl\Table\Alter\ReorganizePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\SimpleAction;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition;
use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition;
use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintType;
use SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use SqlFtw\Sql\Ddl\Table\DropTableCommand;
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;
use SqlFtw\Sql\Ddl\Table\Option\TableOptionsList;
use SqlFtw\Sql\Ddl\Table\Partition\PartitioningDefinition;
use SqlFtw\Sql\Ddl\Table\RenameTableCommand;
use SqlFtw\Sql\QualifiedName;
use function end;

class TableReflection
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Reflection\DatabaseReflection */
    private $database;

    /** @var \SqlFtw\Sql\QualifiedName */
    private $name;

    /** @var \SqlFtw\Sql\Ddl\Table\TableStructureCommand[] */
    private $commands = [];

    /** @var \SqlFtw\Reflection\ColumnReflection[] */
    private $columns = [];

    /**
     * All indexes including those created by columns (ADD COLUMN foo int PRIMARY) and constraints (ADD CONSTRAINT foo UNIQUE KEY ...).
     * Filter primary and unique keys from this array.
     * @var \SqlFtw\Reflection\IndexReflection[]
     */
    private $indexes = [];

    /** @var \SqlFtw\Reflection\ForeignKeyReflection[] */
    private $foreignKeys = [];

    /** @var \SqlFtw\Reflection\TriggerReflection[] */
    private $triggers = [];

    /** @var \SqlFtw\Sql\Ddl\Table\Option\TableOptionsList */
    private $options;

    /** @var \SqlFtw\Sql\Ddl\Table\Partition\PartitioningDefinition|null */
    private $partitioning;

    /// $tablespace

    public function __construct(DatabaseReflection $database, QualifiedName $name, CreateTableCommand $createTableCommand)
    {
        $this->database = $database;
        $this->name = $name;
        $this->commands[] = $createTableCommand;

        foreach ($createTableCommand->getItems() as $item) {
            if ($item instanceof ColumnDefinition) {
                $this->columns[$item->getName()] = new ColumnReflection($this, $item);
                if ($item->getIndexType() !== null) {
                    $this->addIndex(IndexReflection::fromColumn($this, $item));
                }
            } elseif ($item instanceof IndexDefinition) {
                $this->addIndex(new IndexReflection($this, $item));
            } elseif ($item instanceof ConstraintDefinition) {
                $this->addConstraint(new ForeignKeyReflection($this, $item));
                $constraintType = $item->getType();
                if ($constraintType->equals(ConstraintType::PRIMARY_KEY)) {
                    $this->addIndex(IndexReflection::fromConstraint($this, $item));
                } elseif ($constraintType->equals(ConstraintType::UNIQUE_KEY)) {
                    $this->addIndex(IndexReflection::fromConstraint($this, $item));
                }
            }
        }

        $this->options = $createTableCommand->getOptions();
        $this->partitioning = $createTableCommand->getPartitioning();
    }

    public function alter(AlterTableCommand $alterTableCommand): self
    {
        $that = clone($this);
        $that->commands[] = $alterTableCommand;

        foreach ($alterTableCommand->getActions()->getActions() as $action) {
            if ($action instanceof AddColumnAction) {
                $column = $action->getColumn();
                $that->addColumn($column);
            } elseif ($action instanceof AddColumnsAction) {
                foreach ($action->getColumns() as $column) {
                    $that->addColumn($column);
                }
            } elseif ($action instanceof AddConstraintAction) {
                $constraint = $action->getConstraint();
                $constraintType = $constraint->getType();
                if ($constraintType->equals(ConstraintType::PRIMARY_KEY)) {
                    $that->addIndex(new IndexReflection($that, $constraint->getBody()));
                    // MySQL ignores constraint name for indexes, no "constraint" is created, only index
                } elseif ($constraintType->equals(ConstraintType::UNIQUE_KEY)) {
                    $that->addIndex(new IndexReflection($that, $constraint->getBody()));
                    // MySQL ignores constraint name for indexes, no "constraint" is created, only index
                } elseif ($constraintType->equals(ConstraintType::FOREIGN_KEY)) {
                    $that->addForeignKey($constraint->getBody(), $constraint->getName());
                    // MySQL ignores index name on foreign key, no index is created, only constraint
                }
            } elseif ($action instanceof AddForeignKeyAction) {
                $that->addForeignKey($action->getForeignKey(), null);
                // MySQL ignores index name on foreign key, no index is created
            } elseif ($action instanceof AddIndexAction) {
                $that->addIndex(new IndexReflection($that, $action->getIndex()));
            } elseif ($action instanceof AddPartitionAction) {
                ///
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
                $that->columns[$action->getColumn()->getName()] = $action->getColumn();
                /// after, first?
            } elseif ($action instanceof ConvertToCharsetAction) {
                ///
            } elseif ($action instanceof ExchangePartitionAction) {
                ///
            } elseif ($action instanceof ModifyColumnAction) {
                $name = $action->getColumn()->getName();
                $that->getColumn($name);
                $that->columns[$name] = $action->getColumn();
                /// after, first?
            } elseif ($action instanceof RenameIndexAction) {
                $name = $action->getOldName();
                $newName = $action->getNewName();
                $index = $that->getIndex($name);
                $indexDefinition = $index->getIndexDefinition()->duplicateWithNewName($newName);
                unset($that->indexes[$name]);
                $that->indexes[$newName] = new IndexReflection($that, $indexDefinition);
            } elseif ($action instanceof ReorganizePartitionAction) {
                ///
            } elseif ($action instanceof SimpleAction) {
                $type = $action->getType()->getValue();
                switch ($type) {
                    case AlterTableActionType::DROP_COLUMN: // string $name
                        $name = $action->getValue();
                        $this->getColumn($name);
                        unset($that->columns[$name]);
                        break;
                    case AlterTableActionType::DROP_INDEX: // string $name
                        $name = $action->getValue();
                        $that->removeIndex($name);
                        break;
                    case AlterTableActionType::DROP_FOREIGN_KEY: // string $name
                        $name = $action->getValue();
                        $this->getForeignKey($name);
                        unset($that->foreignKeys[$name]);
                        break;
                    case AlterTableActionType::DROP_PRIMARY_KEY:
                        $this->getIndex(IndexDefinition::PRIMARY_KEY_NAME);
                        unset($that->indexes[IndexDefinition::PRIMARY_KEY_NAME]);
                        break;
                    case AlterTableActionType::ORDER_BY: // string[] $columns
                        // ignore (MyISAM only)
                        break;
                    case AlterTableActionType::RENAME_TO: // string $newName
                        ///
                        break;
                    case AlterTableActionType::DISABLE_KEYS:
                        // pass
                        break;
                    case AlterTableActionType::ENABLE_KEYS:
                        // pass
                        break;
                    case AlterTableActionType::DISCARD_TABLESPACE:
                        ///
                        break;
                    case AlterTableActionType::IMPORT_TABLESPACE:
                        ///
                        break;
                    case AlterTableActionType::REMOVE_PARTITIONING:
                        ///
                        break;
                    case AlterTableActionType::UPGRADE_PARTITIONING:
                        ///
                        break;
                    case AlterTableActionType::ANALYZE_PARTITION: // string[] $partitions
                        // pass
                        break;
                    case AlterTableActionType::CHECK_PARTITION: // string[] $partitions
                        // pass
                        break;
                    case AlterTableActionType::COALESCE_PARTITION: // int $number
                        ///
                        break;
                    case AlterTableActionType::DISCARD_PARTITION_TABLESPACE: // string[] $partitions
                        ///
                        break;
                    case AlterTableActionType::DROP_PARTITION: // string[] $partitions
                        ///
                        break;
                    case AlterTableActionType::IMPORT_PARTITION_TABLESPACE: // string[] $partitions
                        ///
                        break;
                    case AlterTableActionType::OPTIMIZE_PARTITION: // string[] $partitions
                        // pass
                        break;
                    case AlterTableActionType::REBUILD_PARTITION: // string[] $partitions
                        // pass
                        break;
                    case AlterTableActionType::REPAIR_PARTITION: //string[] $partitions
                        // pass
                        break;
                    case AlterTableActionType::TRUNCATE_PARTITION: // string[] $partitions
                        ///
                        break;
                    default:
                        throw new NotImplementedException('Unknown action.');
                }
            } else {
                throw new NotImplementedException('Unknown action.');
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
        $that = clone($this);
        $that->commands[] = $renameTableCommand;
        $that->name = $renameTableCommand->getNewNameForTable($this->name);

        return $that;
    }

    /**
     * @param \SqlFtw\Sql\Ddl\Table\RenameTableCommand|\SqlFtw\Sql\Ddl\Table\AlterTableCommand $tableCommand
     * @return \SqlFtw\Reflection\TableReflection
     */
    public function moveByRenaming($tableCommand): self
    {
        $that = clone($this);
        $that->commands[] = $tableCommand;
        $that->columns = $that->indexes = $that->foreignKeys = [];
        $that->options = $that->partitioning = null;

        return $that;
    }

    public function drop(DropTableCommand $dropTableCommand): self
    {
        $that = clone($this);
        $that->commands[] = $dropTableCommand;
        $that->columns = $that->indexes = $that->foreignKeys = [];
        $that->options = $that->partitioning = null;

        return $that;
    }

    public function createIndex(CreateIndexCommand $createIndexCommand): self
    {
        $that = clone($this);
        $that->commands[] = $createIndexCommand;
        $that->addIndex(new IndexReflection($that, $createIndexCommand->getIndex()));

        return $that;
    }

    public function dropIndex(DropIndexCommand $dropIndexCommand): self
    {
        $that = clone($this);
        $that->commands[] = $dropIndexCommand;
        $name = $dropIndexCommand->getName();
        $that->removeIndex($name);

        return $that;
    }

    public function createTrigger(TriggerReflection $trigger): self
    {
        $that = clone($this);
        $name = $trigger->getName();
        $trigger = $this->findTrigger($name);
        if ($trigger !== null) {
            throw new TriggerAlreadyExistsException($name, $this->name->getSchema());
        }
        $that->triggers[$trigger->getName()->getName()] = $trigger;

        return $that;
    }

    public function dropTrigger(string $name): self
    {
        $that = clone($this);
        $this->getTrigger($name);
        unset($that->triggers[$name]);

        return $that;
    }

    // internal setters ------------------------------------------------------------------------------------------------

    private function addColumn(ColumnDefinition $column): void
    {
        $name = $column->getName();
        $currentColumn = $this->findColumn($name);
        if ($currentColumn !== null) {
            throw new ColumnAlreadyExistsException($name, $this->name->getName(), $this->name->getSchema());
        }
        $this->columns[$column->getName()] = new ColumnReflection($this, $column);
        if ($column->getIndexType() !== null) {
            $this->addIndex(IndexReflection::fromColumn($this, $column));
        }
    }

    private function addIndex(IndexReflection $reflection): void
    {
        $index = $reflection->getIndexDefinition();
        $name = $index->getName();
        if ($name === null) {
            $columns = $index->getColumnNames();
            $name = $this->database->getPlatform()->getNamingStrategy()->createIndexName($this, $columns);
            $this->indexes[$name] = $index;
        } else {
            $currentIndex = $this->findIndex($name);
            if ($currentIndex !== null) {
                throw new IndexAlreadyExistsException($name, $this->name->getName(), $this->name->getSchema());
            }
            $this->indexes[$name] = $index;
        }
    }

    private function removeIndex(string $name): void
    {
        $this->getIndex($name);
        unset($this->indexes[$name]);

        /// remove associated constraint
    }

    private function addForeignKey(ForeignKeyDefinition $foreignKey, ?string $name): void
    {
        if ($name !== null) {
            $currentForeignKey = $this->findForeignKey($name);
            if ($currentForeignKey !== null) {
                throw new ForeignKeyAlreadyExistsException($name, $this->name->getName(), $this->name->getSchema());
            }
        } else {
            $name = $this->database->getPlatform()->getNamingStrategy()->createForeignKeyName($this, $foreignKey->getColumns());
        }
    }

    private function removeForeignKey(string $name): void
    {
        $constraint = $this->getForeignKey($name);
        unset($this->foreignKeys[$name]);
        $definition = $constraint->getConstraintDefinition();
        $constraintType = $definition->getType();
        if ($constraintType->equals(ConstraintType::UNIQUE_KEY) || $constraintType->equals(ConstraintType::PRIMARY_KEY)) {
            $this->removeIndex($definition->getBody()->getName());
        }
    }

    /**
     * @param \SqlFtw\Sql\Ddl\Table\Option\TableOptionsList $old
     * @param \SqlFtw\Sql\Ddl\Table\Option\TableOption[] $newOptions
     * @return \SqlFtw\Sql\Ddl\Table\Option\TableOptionsList
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
        ///
    }

    // getters ---------------------------------------------------------------------------------------------------------

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function wasDropped(): bool
    {
        return end($this->commands) instanceof DropTableCommand;
    }

    public function wasMoved(): bool
    {
        if ($this->columns !== []) {
            return false;
        }
        $command = end($this->commands);

        return $command instanceof RenameTableCommand
            || ($command instanceof AlterTableCommand && $command->getActions()->getActionsByType(AlterTableActionType::get(AlterTableActionType::RENAME_TO)));
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Table\CreateTableCommand[]|\SqlFtw\Sql\Ddl\Table\AlterTableCommand[]|\SqlFtw\Sql\Ddl\Table\DropTableCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @return \SqlFtw\Reflection\ColumnReflection[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumn(string $name): ColumnReflection
    {
        $column = $this->columns[$name] ?? null;
        if ($column === null) {
            throw new ColumnDoesNotExistException($name, $this->name->getName(), $this->name->getSchema());
        }

        return $column;
    }

    public function findColumn(string $name): ?ColumnReflection
    {
        return $this->columns[$name] ?? null;
    }

    /**
     * @return \SqlFtw\Reflection\IndexReflection[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getIndex(string $name): IndexReflection
    {
        $index = $this->indexes[$name] ?? null;
        if ($index === null) {
            throw new IndexDoesNotExistException($name, $this->name->getName(), $this->name->getSchema());
        }

        return $index;
    }

    public function findIndex(string $name): ?IndexReflection
    {
        return $this->indexes[$name] ?? null;
    }

    /**
     * @return \SqlFtw\Reflection\ForeignKeyReflection[]
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
     * @return \SqlFtw\Reflection\TriggerReflection[]
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

    public function getPartitioning(): ?PartitioningDefinition
    {
        return $this->partitioning;
    }

}
