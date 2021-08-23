<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Replication;

use Dogma\StrictBehaviorMixin;
use Dogma\Time\DateTime;
use SqlFtw\Formatter\Formatter;

class PurgeBinaryLogsCommand implements ReplicationCommand
{
    use StrictBehaviorMixin;

    /** @var string|null */
    private $toLog;

    /** @var DateTime|null */
    private $before;

    public function __construct(?string $toLog, ?DateTime $before)
    {
        $this->toLog = $toLog;
        $this->before = $before;
    }

    public function getToLog(): ?string
    {
        return $this->toLog;
    }

    public function getBefore(): ?DateTime
    {
        return $this->before;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'PURGE BINARY LOGS';
        if ($this->toLog !== null) {
            $result .= ' TO ' . $formatter->formatString($this->toLog);
        } else {
            $result .= ' BEFORE ' . $formatter->formatDateTime($this->before);
        }

        return $result;
    }

}
