<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use SqlFtw\Sql\Keyword;
use SqlFtw\SqlFormatter\SqlFormatter;

class ReorganizePartitionAction implements \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string|string[] */
    private $partitions;

    /** @var \SqlFtw\Sql\Ddl\Table\Partition\PartitionDefinition[] */
    private $newPartitions;

    /**
     * @param string|string[] $partitions
     * @param \SqlFtw\Sql\Ddl\Table\Partition\PartitionDefinition[] $newPartitions
     */
    public function __construct($partitions, array $newPartitions)
    {
        $this->partitions = $partitions;
        $this->newPartitions = $newPartitions;
    }

    public function getType(): AlterTableActionType
    {
        return AlterTableActionType::get(AlterTableActionType::REORGANIZE_PARTITION);
    }

    /**
     * @return string|string[]
     */
    public function getPartitions()
    {
        return $this->partitions;
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Table\Partition\PartitionDefinition[]
     */
    public function getNewPartitions(): array
    {
        return $this->newPartitions;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'REORGANIZE PARTITION ' . ($this->partitions === Keyword::ALL ? 'ALL' : $formatter->formatNamesList($this->partitions))
            . ' INTO (' . $formatter->formatSerializablesList($this->newPartitions) . ')';
    }

}
