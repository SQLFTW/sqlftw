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

class RepairPartitionAction extends PartitioningAction
{

    /** @var non-empty-list<string>|null */
    public ?array $partitions;

    /**
     * @param non-empty-list<string>|null $partitions
     */
    public function __construct(?array $partitions = null)
    {
        $this->partitions = $partitions;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'REPAIR PARTITION ';

        if ($this->partitions === null) {
            return $result . 'ALL';
        } else {
            return $result . $formatter->formatNamesList($this->partitions);
        }
    }

}
