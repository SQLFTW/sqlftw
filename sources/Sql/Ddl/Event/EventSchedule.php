<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Event;

use DateInterval;
use Dogma\Check;
use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use Dogma\Time\Span\DateTimeSpan;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\TimeExpression;
use SqlFtw\Sql\Expression\TimeInterval;
use SqlFtw\Sql\SqlSerializable;

class EventSchedule implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var TimeExpression|null */
    private $time;

    /** @var TimeInterval|null */
    private $interval;

    /** @var TimeExpression|null */
    private $startTime;

    /** @var TimeExpression|null */
    private $endTime;

    /**
     * @param TimeInterval|DateInterval|DateTimeSpan|null $interval
     */
    public function __construct(
        ?TimeExpression $time,
        $interval = null,
        ?TimeExpression $startTime = null,
        ?TimeExpression $endTime = null
    ) {
        Check::oneOf($time, $interval);

        if ($interval !== null && !$interval instanceof TimeInterval) {
            $interval = TimeInterval::create($interval);
        }

        $this->interval = $interval;
        $this->time = $time;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    public function serialize(Formatter $formatter): string
    {
        if ($this->time !== null) {
            $result = 'AT ' . $this->time->serialize($formatter);
        } elseif ($this->interval !== null) {
            $result = 'EVERY ' . $this->interval->serialize($formatter);
        } else {
            throw new ShouldNotHappenException('Either time or interval must be set.');
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
