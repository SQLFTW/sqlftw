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
use Dogma\StrictBehaviorMixin;
use Dogma\Time\Span\DateTimeSpan;
use SqlFtw\Formatter\Formatter;

/**
 * e.g. INTERVAL 6 DAYS
 */
class IntervalLiteral implements Literal
{
    use StrictBehaviorMixin;

    /** @var TimeInterval|DateTimeSpan|DateInterval */
    private $value;

    /**
     * @param TimeInterval|DateTimeSpan|DateInterval $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return TimeInterval|DateTimeSpan|DateInterval
     */
    public function getValue()
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'INTERVAL ' . $formatter->formatValue($this->value);
    }

}
