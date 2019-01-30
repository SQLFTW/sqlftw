<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Event;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\TimeExpression;
use SqlFtw\Sql\Expression\TimeInterval;
use SqlFtw\Sql\SqlSerializable;

class EventSchedule implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\TimeInterval|null */
    private $interval;

    /** @var \SqlFtw\Sql\Expression\TimeExpression|null */
    private $time;

    /** @var \SqlFtw\Sql\Expression\TimeExpression|null */
    private $startTime;

    /** @var \SqlFtw\Sql\Expression\TimeExpression|null */
    private $endTime;

    /**
     * @param \SqlFtw\Sql\Expression\TimeInterval|\DateInterval|\Dogma\Time\Span\DateTimeSpan $interval
     * @param \SqlFtw\Sql\Expression\TimeExpression|null $time
     * @param \SqlFtw\Sql\Expression\TimeExpression|null $startTime
     * @param \SqlFtw\Sql\Expression\TimeExpression|null $endTime
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

    public function serialize(Formatter $formatter): string
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
