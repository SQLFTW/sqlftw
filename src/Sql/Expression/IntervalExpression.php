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
use SqlFtw\Sql\NodeType;
use SqlFtw\Sql\Time\TimeInterval;
use SqlFtw\SqlFormatter\SqlFormatter;

class IntervalExpression implements \SqlFtw\Sql\Expression\ExpressionNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Time\TimeInterval|\Dogma\Time\DateTimeInterval|\DateInterval */
    private $interval;

    /**
     * @param \SqlFtw\Sql\Time\TimeInterval|\Dogma\Time\DateTimeInterval|\DateInterval $interval
     */
    public function __construct($interval)
    {
        Check::types($interval, [TimeInterval::class, DateTimeInterval::class, \DateInterval::class]);

        $this->interval = $interval;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::PARENTHESES);
    }

    /**
     * @return \SqlFtw\Sql\Time\TimeInterval|\Dogma\Time\DateTimeInterval|\DateInterval
     */
    public function getInterval()
    {
        return $this->interval;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'INTERVAL ' . $formatter->formatValue($this->interval);
    }

}
