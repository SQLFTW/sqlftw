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
use DateTimeInterface;
use Dogma\Time\Date;
use Dogma\Time\DateTime;
use Dogma\Time\Span\DateTimeSpan;
use Dogma\Time\Time;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;

class TimeExpression implements SqlSerializable
{

    /** @var Date|Time|DateTime|BuiltInFunction */
    private $initial;

    /** @var TimeInterval[] */
    private $intervals;

    /**
     * @param Date|Time|DateTimeInterface|BuiltInFunction $initial
     * @param DateInterval[]|DateTimeSpan[]|TimeInterval[] $intervals
     */
    public function __construct($initial, array $intervals = [])
    {
        if ($initial instanceof DateTimeInterface && !$initial instanceof DateTime) {
            $initial = DateTime::createFromDateTimeInterface($initial);
        }

        $int = [];
        foreach ($intervals as $interval) {
            if ($interval instanceof TimeInterval) {
                $int[] = $interval;
            } else {
                foreach (TimeInterval::createIntervals($interval) as $ii) {
                    $int[] = $ii;
                }
            }
        }

        // todo
        /*if ($value instanceof BuiltInFunction && !$value->isTime()) {
            throw new InvalidDefinitionException(
                sprintf('Invalid function. A time returning function expected. %s given.', $value->getValue())
            );
        }*/

        $this->initial = $initial;
        $this->intervals = $int;
    }

    /**
     * @return Date|Time|DateTime|BuiltInFunction
     */
    public function getInitial()
    {
        return $this->initial;
    }

    /**
     * @return TimeInterval[]
     */
    public function getIntervals(): array
    {
        return $this->intervals;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->initial instanceof BuiltInFunction
            ? $this->initial->serialize($formatter)
            : $formatter->formatValue($this->initial);

        foreach ($this->intervals as $interval) {
            $result .= ' + INTERVAL ' . $interval->serialize($formatter);
        }

        return $result;
    }

}
