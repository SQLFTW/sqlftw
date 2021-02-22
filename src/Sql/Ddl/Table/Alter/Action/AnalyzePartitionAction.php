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

class AnalyzePartitionAction implements PartitioningAction
{
    use StrictBehaviorMixin;

    /** @var string[]|null */
    private $partitions;

    /**
     * @param string[]|null $partitions
     */
    public function __construct(?array $partitions = null)
    {
        $this->partitions = $partitions;
    }

    /**
     * @return string[]|null
     */
    public function getPartitions(): ?array
    {
        return $this->partitions;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ANALYZE PARTITION ';

        if ($this->partitions === null) {
            return $result . 'ALL';
        } else {
            return $result . $formatter->formatNamesList($this->partitions);
        }
    }

}
