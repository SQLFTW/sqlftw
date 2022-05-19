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

class AddPartitionAction implements PartitioningAction
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<PartitionDefinition> */
    private $partitions;

    /**
     * @param non-empty-array<PartitionDefinition> $partition
     */
    public function __construct(array $partition)
    {
        $this->partitions = $partition;
    }

    /**
     * @return non-empty-array<PartitionDefinition>
     */
    public function getPartitions(): array
    {
        return $this->partitions;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ADD PARTITION (' . $formatter->formatSerializablesList($this->partitions) . ')';
    }

}
