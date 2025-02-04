<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;

class ShowRelaylogEventsCommand extends ShowCommand
{

    public ?string $logName;

    public ?int $from;

    public ?int $limit;

    public ?int $offset;

    public ?string $channel;

    public function __construct(?string $logName, ?int $from, ?int $limit, ?int $offset, ?string $channel)
    {
        $this->logName = $logName;
        $this->from = $from;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->channel = $channel;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SHOW RELAYLOG EVENTS';
        if ($this->logName !== null) {
            $result .= ' IN ' . $formatter->formatString($this->logName);
        }
        if ($this->from !== null) {
            $result .= ' FROM ' . $this->from;
        }
        if ($this->limit !== null) {
            $result .= ' LIMIT ' . $this->limit;
            if ($this->offset !== null) {
                $result .= ' OFFSET ' . $this->offset;
            }
        }
        if ($this->channel !== null) {
            $result .= ' FOR CHANNEL ' . $formatter->formatString($this->channel);
        }

        return $result;
    }

}
