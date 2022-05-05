<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use DateInterval;
use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use Dogma\Time\Span\DateTimeSpan;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\SqlSerializable;
use function count;
use function is_string;
use function sprintf;

class TimeInterval implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var int|string|ExpressionNode */
    private $value;

    /** @var TimeIntervalUnit */
    private $unit;

    /**
     * @param int|string|ExpressionNode $value
     */
    public function __construct($value, TimeIntervalUnit $unit)
    {
        $this->value = $value;
        $this->unit = $unit;
    }

    /**
     * @param DateInterval|DateTimeSpan $interval
     */
    public static function create($interval): self
    {
        $intervals = self::createIntervals($interval);
        if (count($intervals) !== 1) {
            throw new InvalidDefinitionException('Invalid interval. Only a single value expected.');
        }

        return $intervals[0];
    }

    /**
     * @param DateInterval|DateTimeSpan $interval
     * @return self[]
     */
    public static function createIntervals($interval): array
    {
        Check::types($interval, [DateInterval::class, DateTimeSpan::class]);

        if ($interval instanceof DateInterval) {
            $interval = DateTimeSpan::createFromDateInterval($interval);
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
                    sprintf('%d %d:%d:%d.%d', $interval->getDays(), $interval->getHours(), $interval->getMinutes(), $interval->getSeconds(), $interval->getMicroseconds()),
                    TimeIntervalUnit::get(TimeIntervalUnit::DAY_MICROSECOND)
                );
            } elseif ($interval->getSeconds() !== 0) {
                $intervals[] = new self(
                    sprintf('%d %d:%d:%d', $interval->getDays(), $interval->getHours(), $interval->getMinutes(), $interval->getSeconds()),
                    TimeIntervalUnit::get(TimeIntervalUnit::DAY_SECOND)
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
                    sprintf('%d:%d:%d.%d', $interval->getHours(), $interval->getMinutes(), $interval->getSeconds(), $interval->getMicroseconds()),
                    TimeIntervalUnit::get(TimeIntervalUnit::HOUR_MICROSECOND)
                );
            } elseif ($interval->getSeconds() !== 0) {
                $intervals[] = new self(
                    sprintf('%d:%d:%d', $interval->getHours(), $interval->getMinutes(), $interval->getSeconds()),
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
                    sprintf('%d:%d.%d', $interval->getMinutes(), $interval->getSeconds(), $interval->getMicroseconds()),
                    TimeIntervalUnit::get(TimeIntervalUnit::MINUTE_MICROSECOND)
                );
            } elseif ($interval->getSeconds() !== 0) {
                $intervals[] = new self(
                    sprintf('%d:%d', $interval->getMinutes(), $interval->getSeconds()),
                    TimeIntervalUnit::get(TimeIntervalUnit::MINUTE_SECOND)
                );
            } else {
                $intervals[] = new self($interval->getMinutes(), TimeIntervalUnit::get(TimeIntervalUnit::MINUTE));
            }
        } elseif ($interval->getSeconds() !== 0) {
            if ($interval->getMicroseconds() !== 0) {
                $intervals[] = new self(
                    sprintf('%d.%d', $interval->getSeconds(), $interval->getMicroseconds()),
                    TimeIntervalUnit::get(TimeIntervalUnit::SECOND_MICROSECOND)
                );
            } else {
                $intervals[] = new self($interval->getSeconds(), TimeIntervalUnit::get(TimeIntervalUnit::SECOND));
            }
        } elseif ($interval->getMicroseconds() !== 0) {
            $intervals[] = new self($interval->getMicroseconds(), TimeIntervalUnit::get(TimeIntervalUnit::MICROSECOND));
        }

        return $intervals;
    }

    /**
     * @return int|string|ExpressionNode
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
        if ($this->value instanceof ExpressionNode) {
            $result = $this->value->serialize($formatter);
        } elseif (is_string($this->value)) {
            $result = $formatter->formatString($this->value);
        } else {
            $result = $this->value;
        }

        return $result . ' ' . $this->unit->serialize($formatter);
    }

}
