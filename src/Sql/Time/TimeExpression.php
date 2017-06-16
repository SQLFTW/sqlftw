<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Time;

use Dogma\Check;
use Dogma\Time\Date;
use Dogma\Time\DateTime;
use Dogma\Time\Time;
use SqlFtw\Sql\Platform\Mysql\MysqlConstant;
use SqlFtw\SqlFormatter\SqlFormatter;

class TimeExpression implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \Dogma\Time\Date|\Dogma\Time\Time|\Dogma\Time\DateTime|\SqlFtw\Sql\Platform\Mysql\MysqlConstant */
    private $value;

    /** @var \SqlFtw\Sql\Time\TimeInterval[] */
    private $intervals;

    /**
     * @param \Dogma\Time\Date|\Dogma\Time\Time|\DateTimeInterface|\SqlFtw\Sql\Platform\Mysql\MysqlConstant $value
     * @param \Dogma\Time\DateTimeInterval[]|\DateInterval[] $intervals
     */
    public function __construct($value, array $intervals = [])
    {
        Check::types($value, [Date::class, Time::class, DateTime::class, MysqlConstant::class]);
        if ($value instanceof \DateTimeInterface && !$value instanceof DateTime) {
            $value = DateTime::createFromDateTimeInterface($value);
        }
        foreach ($intervals as $i => $interval) {
            if (!$interval instanceof TimeInterval) {
                unset($intervals[$i]);
                foreach (TimeInterval::createIntervals($interval) as $ii) {
                    $intervals[] = $ii;
                }
            }
        }

        if ($value instanceof MysqlConstant && !$value->isTime()) {
            throw new \SqlFtw\Sql\InvalidDefinitionException(
                sprintf('Invalid MySQL constant. A time constant expected. %s given.', $value->getValue())
            );
        }

        $this->value = $value;
        $this->intervals = $intervals;
    }

    /**
     * @return \Dogma\Time\Date|\Dogma\Time\Time|\Dogma\Time\DateTime|\SqlFtw\Sql\Platform\Mysql\MysqlConstant
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return \Dogma\Time\DateTimeInterval[]
     */
    public function getIntervals(): array
    {
        return $this->intervals;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = $this->value instanceof MysqlConstant
            ? $this->value->serialize($formatter)
            : $formatter->formatValue($this->value);

        foreach ($this->intervals as $interval) {
            $result .= ' + INTERVAL ' . $interval->serialize($formatter);
        }

        return $result;
    }

}
