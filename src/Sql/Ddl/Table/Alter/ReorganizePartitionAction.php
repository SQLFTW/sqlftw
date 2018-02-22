<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use Dogma\Check;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Keyword;

class ReorganizePartitionAction implements \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string[]|null */
    private $partitions;

    /** @var \SqlFtw\Sql\Ddl\Table\Partition\PartitionDefinition[] */
    private $newPartitions;

    /**
     * @param string[]|null $partitions (where null means ALL)
     * @param \SqlFtw\Sql\Ddl\Table\Partition\PartitionDefinition[] $newPartitions
     */
    public function __construct(?array $partitions, array $newPartitions)
    {
        if ($partitions !== null) {
            Check::itemsOfType($partitions, Type::STRING);
        }
        $this->partitions = $partitions;
        $this->newPartitions = $newPartitions;
    }

    public function getType(): AlterTableActionType
    {
        return AlterTableActionType::get(AlterTableActionType::REORGANIZE_PARTITION);
    }

    /**
     * @return string[]|null
     */
    public function getPartitions(): ?array
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

    public function serialize(Formatter $formatter): string
    {
        return 'REORGANIZE PARTITION ' . ($this->partitions === null ? 'ALL' : $formatter->formatNamesList($this->partitions))
            . ' INTO (' . $formatter->formatSerializablesList($this->newPartitions) . ')';
    }

}
