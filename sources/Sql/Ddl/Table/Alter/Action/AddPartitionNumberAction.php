<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use SqlFtw\Formatter\Formatter;

class AddPartitionNumberAction implements PartitioningAction
{

    /** @var int */
    private $partitions;

    public function __construct(int $partition)
    {
        $this->partitions = $partition;
    }

    public function getPartitions(): int
    {
        return $this->partitions;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ADD PARTITION PARTITIONS ' . $this->partitions;
    }

}
