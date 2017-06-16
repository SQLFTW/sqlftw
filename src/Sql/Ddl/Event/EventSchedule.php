<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Event;

use SqlFtw\Sql\Time\TimeExpression;
use SqlFtw\Sql\Time\TimeInterval;
use SqlFtw\SqlFormatter\SqlFormatter;

class EventSchedule implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Time\TimeInterval|null */
    private $interval;

    /** @var \SqlFtw\Sql\Time\TimeExpression|null */
    private $time;

    /** @var \SqlFtw\Sql\Time\TimeExpression|null */
    private $startTime;

    /** @var \SqlFtw\Sql\Time\TimeExpression|null */
    private $endTime;

    /**
     * @param \SqlFtw\Sql\Time\TimeInterval|\DateInterval|\Dogma\Time\DateTimeInterval $interval
     * @param \SqlFtw\Sql\Time\TimeExpression|null $time
     * @param \SqlFtw\Sql\Time\TimeExpression|null $startTime
     * @param \SqlFtw\Sql\Time\TimeExpression|null $endTime
     */
    public function __construct($interval, ?TimeExpression $time = null, ?TimeExpression $startTime = null, ?TimeExpression $endTime = null)
    {
        if (!$interval instanceof TimeInterval) {
            $interval = TimeInterval::create($interval);
        }

        $this->interval = $interval;
        $this->time = $time;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        if ($this->interval !== null) {
            $result = 'EVERY ' . $this->interval->serialize($formatter);
        } else {
            $result = 'AT ' . $this->time->serialize($formatter);
        }
        if ($this->startTime !== null) {
            $result .= ' STARTS ' . $this->startTime->serialize($formatter);
        }
        if ($this->endTime !== null) {
            $result .= ' ENDS ' . $this->endTime->serialize($formatter);
        }

        return $result;
    }

}
