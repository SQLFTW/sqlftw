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
use SqlFtw\Sql\Ddl\Table\Alter\Action\AnalyzePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\CheckPartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\CoalescePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DiscardPartitionTablespaceAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropPartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ExchangePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ImportPartitionTablespaceAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\OptimizePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\PartitioningAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RebuildPartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RemovePartitioningAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RepairPartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\TruncatePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\UpgradePartitioningAction;
use SqlFtw\Sql\Ddl\Table\Partition\PartitioningDefinition;

class PartitioningReflection
{
    use StrictBehaviorMixin;

    /** @var TableReflection */
    private $table;

    /** @var PartitioningDefinition|null */
    private $definition;

    public function __construct(TableReflection $table, ?PartitioningDefinition $definition)
    {
        $this->table = $table;
        $this->definition = $definition;
    }

    public function apply(PartitioningAction $action): self
    {
        if ($action instanceof AnalyzePartitionAction || $action instanceof CheckPartitionAction) {
            //pass
            return $this;
        } elseif ($action instanceof CoalescePartitionAction) {
            // todo
        } elseif ($action instanceof DiscardPartitionTablespaceAction) {
            // todo
        } elseif ($action instanceof DropPartitionAction) {
            // todo
        } elseif ($action instanceof ExchangePartitionAction) {
            // todo
        } elseif ($action instanceof ImportPartitionTablespaceAction) {
            // todo
        } elseif ($action instanceof OptimizePartitionAction || $action instanceof RebuildPartitionAction || $action instanceof RepairPartitionAction) {
            // pass
            return $this;
        } elseif ($action instanceof RemovePartitioningAction) {
            // todo
        } elseif ($action instanceof TruncatePartitionAction) {
            // todo
        } elseif ($action instanceof UpgradePartitioningAction) {
            // todo
        } else {
            throw new ShouldNotHappenException('Unknown action.');
        }

        return $this;
    }

    public function getTable(): TableReflection
    {
        return $this->table;
    }

    public function getDefinition(): ?PartitioningDefinition
    {
        return $this->definition;
    }

}
