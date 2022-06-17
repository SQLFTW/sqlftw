<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class ShowRelaylogEventsCommand extends Statement implements ShowCommand
{
    use StrictBehaviorMixin;

    /** @var string|null */
    private $logName;

    /** @var int|null */
    private $from;

    /** @var int|null */
    private $limit;

    /** @var int|null */
    private $offset;

    public function __construct(?string $logName, ?int $from, ?int $limit, ?int $offset)
    {
        $this->logName = $logName;
        $this->from = $from;
        $this->limit = $limit;
        $this->offset = $offset;
    }

    public function getLogName(): ?string
    {
        return $this->logName;
    }

    public function getFrom(): ?int
    {
        return $this->from;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
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
            $result .= ' LIMIT ';
            if ($this->offset !== null) {
                $result .= $this->offset . ', ';
            }
            $result .= $this->limit;
        }

        return $result;
    }

}
