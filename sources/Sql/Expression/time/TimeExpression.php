<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;

class TimeExpression implements SqlSerializable
{

    private RootNode $initial;

    /** @var list<TimeInterval> */
    private array $intervals;

    /**
     * @param list<TimeInterval> $intervals
     */
    public function __construct(RootNode $initial, array $intervals = [])
    {
        $this->initial = $initial;
        $this->intervals = $intervals;
    }

    public function getInitial(): RootNode
    {
        return $this->initial;
    }

    /**
     * @return list<TimeInterval>
     */
    public function getIntervals(): array
    {
        return $this->intervals;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->initial->serialize($formatter);

        foreach ($this->intervals as $interval) {
            $result .= ' + ' . $interval->serialize($formatter);
        }

        return $result;
    }

}
