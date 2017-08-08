<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\Check;
use Dogma\Time\DateTimeInterval;
use SqlFtw\Formatter\Formatter;

class TimeInterval implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var int|string */
    private $value;

    /** @var \SqlFtw\Sql\Expression\TimeIntervalUnit */
    private $unit;

    /**
     * @param int|string $value
     * @param \SqlFtw\Sql\Expression\TimeIntervalUnit $unit
     */
    public function __construct($value, TimeIntervalUnit $unit)
    {
        $this->value = $value;
        $this->unit = $unit;
    }

    /**
     * @param \DateInterval|\Dogma\Time\DateTimeInterval $interval
     * @return self
     */
    public static function create($interval): self
    {
        $intervals = self::createIntervals($interval);
        if (count($intervals) !== 1) {
            throw new \SqlFtw\Sql\InvalidDefinitionException('Invalid interval. Only a single value expected.');
        }
        return $intervals[0];
    }

    /**
     * @param \DateInterval|\Dogma\Time\DateTimeInterval $interval
     * @return self[]
     */
    public static function createIntervals($interval): array
    {
        Check::types($interval, [\DateInterval::class, DateTimeInterval::class]);

        if ($interval instanceof \DateInterval) {
            $interval = new DateTimeInterval($interval);
        }

        $intervals = [];
        if ($interval->getYears() !== 0) {
            if ($interval->getMonths() !== 0) {
                $intervals[] = new self(
                    sprintf('%d-%d', $interval->getYears(), $interval->getMonths()),
                    TimeIntervalUnit::get(TimeIntervalUnit::YEAR_MONTH)
                );
            } else {
                $intervals[] = new self($interval->getYears(), TimeIntervalUnit::get(TimeIntervalUnit::YEAR));
            }
        } elseif ($interval->getMonths() !== 0) {
            $intervals[] = new self($interval->getMonths(), TimeIntervalUnit::get(TimeIntervalUnit::MONTH));
        }
        if ($interval->getDays() !== 0) {
            if ($interval->getMicroseconds() !== 0) {
                $intervals[] = new self(
                    sprintf('%d %d:%d:%d.%d', $interval->getDays(), $interval->getHours(), $interval->getMinutes(), $interval->getSecondsFloored(), $interval->getMicroseconds()),
                    TimeIntervalUnit::get(TimeIntervalUnit::DAY_MICROSECOND)
                );
            } elseif ($interval->getSecondsFloored() !== 0) {
                $intervals[] = new self(
                    sprintf('%d %d:%d:%d', $interval->getDays(), $interval->getHours(), $interval->getMinutes(), $interval->getSecondsFloored()),
                    TimeIntervalUnit::get(TimeIntervalUnit::DAY_HOUR)
                );
            } elseif ($interval->getMinutes() !== 0) {
                $intervals[] = new self(
                    sprintf('%d %d:%d', $interval->getDays(), $interval->getHours(), $interval->getMinutes()),
                    TimeIntervalUnit::get(TimeIntervalUnit::DAY_MINUTE)
                );
            } elseif ($interval->getHours() !== 0) {
                $intervals[] = new self(
                    sprintf('%d %d', $interval->getDays(), $interval->getHours()),
                    TimeIntervalUnit::get(TimeIntervalUnit::DAY_HOUR)
                );
            } else {
                $intervals[] = new self($interval->getDays(), TimeIntervalUnit::get(TimeIntervalUnit::DAY));
            }
        } elseif ($interval->getHours() !== 0) {
            if ($interval->getMicroseconds() !== 0) {
                $intervals[] = new self(
                    sprintf('%d:%d:%d.%d', $interval->getHours(), $interval->getMinutes(), $interval->getSecondsFloored(), $interval->getMicroseconds()),
                    TimeIntervalUnit::get(TimeIntervalUnit::HOUR_MICROSECOND)
                );
            } elseif ($interval->getSecondsFloored() !== 0) {
                $intervals[] = new self(
                    sprintf('%d:%d:%d', $interval->getHours(), $interval->getMinutes(), $interval->getSecondsFloored()),
                    TimeIntervalUnit::get(TimeIntervalUnit::HOUR_SECOND)
                );
            } elseif ($interval->getMinutes() !== 0) {
                $intervals[] = new self(
                    sprintf('%d:%d', $interval->getHours(), $interval->getMinutes()),
                    TimeIntervalUnit::get(TimeIntervalUnit::HOUR_MINUTE)
                );
            } else {
                $intervals[] = new self($interval->getHours(), TimeIntervalUnit::get(TimeIntervalUnit::HOUR));
            }
        } elseif ($interval->getMinutes() !== 0) {
            if ($interval->getMicroseconds() !== 0) {
                $intervals[] = new self(
                    sprintf('%d:%d.%d', $interval->getMinutes(), $interval->getSecondsFloored(), $interval->getMicroseconds()),
                    TimeIntervalUnit::get(TimeIntervalUnit::MINUTE_MICROSECOND)
                );
            } elseif ($interval->getSecondsFloored() !== 0) {
                $intervals[] = new self(
                    sprintf('%d:%d', $interval->getMinutes(), $interval->getSecondsFloored()),
                    TimeIntervalUnit::get(TimeIntervalUnit::MINUTE_SECOND)
                );
            } else {
                $intervals[] = new self($interval->getMinutes(), TimeIntervalUnit::get(TimeIntervalUnit::MINUTE));
            }
        } elseif ($interval->getSecondsFloored() !== 0) {
            if ($interval->getMicroseconds() !== 0) {
                $intervals[] = new self(
                    sprintf('%d.%d', $interval->getSecondsFloored(), $interval->getMicroseconds()),
                    TimeIntervalUnit::get(TimeIntervalUnit::SECOND_MICROSECOND)
                );
            } else {
                $intervals[] = new self($interval->getSecondsFloored(), TimeIntervalUnit::get(TimeIntervalUnit::SECOND));
            }
        } elseif ($interval->getMicroseconds() !== 0) {
            $intervals[] = new self($interval->getMicroseconds(), TimeIntervalUnit::get(TimeIntervalUnit::MICROSECOND));
        }

        return $intervals;
    }

    /**
     * @return int|string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getUnit(): TimeIntervalUnit
    {
        return $this->unit;
    }

    public function serialize(Formatter $formatter): string
    {
        return (is_string($this->value) ? $formatter->formatString($this->value) : $this->value)
            . ' ' . $this->unit->serialize($formatter);
    }

}
