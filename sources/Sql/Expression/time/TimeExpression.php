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

    /** @var RootNode */
    private $initial;

    /** @var TimeInterval[] */
    private $intervals;

    /**
     * @param TimeInterval[] $intervals
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
     * @return TimeInterval[]
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
