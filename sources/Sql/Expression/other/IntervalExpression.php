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
use Dogma\Time\Span\DateTimeSpan;
use SqlFtw\Formatter\Formatter;

/**
 * e.g. INTERVAL 6 DAYS
 */
class IntervalExpression implements RootNode
{

    /** @var TimeInterval */
    private $value;

    /**
     * @param TimeInterval|DateTimeSpan|DateInterval $value
     */
    public function __construct($value)
    {
        if (!$value instanceof TimeInterval) {
            $value = TimeInterval::create($value);
        }

        $this->value = $value;
    }

    public function getInterval(): TimeInterval
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'INTERVAL ' . $formatter->formatValue($this->value);
    }

}
