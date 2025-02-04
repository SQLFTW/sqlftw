<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Replication;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;

class ChangeReplicationFilterCommand extends Command implements ReplicationCommand
{

    /** @var non-empty-list<ReplicationFilter> */
    public array $filters;

    public ?string $channel;

    /**
     * @param non-empty-list<ReplicationFilter> $filters
     */
    public function __construct(array $filters, ?string $channel = null)
    {
        $this->filters = $filters;
        $this->channel = $channel;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = "CHANGE REPLICATION FILTER\n  " . $formatter->formatNodesList($this->filters);

        if ($this->channel !== null) {
            $result .= "\n  FOR CHANNEL " . $formatter->formatName($this->channel);
        }

        return $result;
    }

}
