<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Partition\PartitionDefinition;

class ReorganizePartitionAction implements PartitioningAction
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<string> */
    private $partitions;

    /** @var non-empty-array<PartitionDefinition> */
    private $newPartitions;

    /**
     * @param non-empty-array<string> $partitions
     * @param non-empty-array<PartitionDefinition> $newPartitions
     */
    public function __construct(array $partitions, array $newPartitions)
    {
        $this->partitions = $partitions;
        $this->newPartitions = $newPartitions;
    }

    /**
     * @return non-empty-array<string>
     */
    public function getPartitions(): array
    {
        return $this->partitions;
    }

    /**
     * @return non-empty-array<PartitionDefinition>
     */
    public function getNewPartitions(): array
    {
        return $this->newPartitions;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'REORGANIZE PARTITION ' . $formatter->formatNamesList($this->partitions)
            . ' INTO (' . $formatter->formatSerializablesList($this->newPartitions) . ')';
    }

}
