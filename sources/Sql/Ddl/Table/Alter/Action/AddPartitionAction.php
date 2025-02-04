<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Partition\PartitionDefinition;

class AddPartitionAction extends PartitioningAction
{

    /** @var non-empty-list<PartitionDefinition> */
    public array $partitions;

    /**
     * @param non-empty-list<PartitionDefinition> $partition
     */
    public function __construct(array $partition)
    {
        $this->partitions = $partition;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ADD PARTITION (' . $formatter->formatNodesList($this->partitions) . ')';
    }

}
