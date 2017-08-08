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
use Dogma\Time\Date;
use Dogma\Time\DateTime;
use Dogma\Time\Time;
use SqlFtw\Formatter\Formatter;

class TimeExpression implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \Dogma\Time\Date|\Dogma\Time\Time|\Dogma\Time\DateTime|\SqlFtw\Sql\Expression\BuiltInFunction */
    private $value;

    /** @var \SqlFtw\Sql\Expression\TimeInterval[] */
    private $intervals;

    /**
     * @param \Dogma\Time\Date|\Dogma\Time\Time|\DateTimeInterface|\SqlFtw\Sql\Expression\BuiltInFunction $value
     * @param \Dogma\Time\DateTimeInterval[]|\DateInterval[]|\SqlFtw\Sql\Expression\TimeInterval[] $intervals
     */
    public function __construct($value, array $intervals = [])
    {
        Check::types($value, [Date::class, Time::class, DateTime::class, BuiltInFunction::class]);
        if ($value instanceof \DateTimeInterface && !$value instanceof DateTime) {
            $value = DateTime::createFromDateTimeInterface($value);
        }
        /** @var \SqlFtw\Sql\Expression\TimeInterval[] $intervals */
        $int = [];
        foreach ($intervals as $i => $interval) {
            if ($interval instanceof TimeInterval) {
                $int[] = $interval;
            } else {
                foreach (TimeInterval::createIntervals($interval) as $ii) {
                    $int[] = $ii;
                }
            }
        }

        if ($value instanceof BuiltInFunction && !$value->isTime()) {
            throw new \SqlFtw\Sql\InvalidDefinitionException(
                sprintf('Invalid function. A time returning function expected. %s given.', $value->getValue())
            );
        }

        $this->value = $value;
        $this->intervals = $int;
    }

    /**
     * @return \Dogma\Time\Date|\Dogma\Time\Time|\Dogma\Time\DateTime|\SqlFtw\Sql\Expression\BuiltInFunction
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return \SqlFtw\Sql\Expression\TimeInterval[]
     */
    public function getIntervals(): array
    {
        return $this->intervals;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->value instanceof BuiltInFunction
            ? $this->value->serialize($formatter)
            : $formatter->formatValue($this->value);

        foreach ($this->intervals as $interval) {
            $result .= ' + INTERVAL ' . $interval->serialize($formatter);
        }

        return $result;
    }

}
