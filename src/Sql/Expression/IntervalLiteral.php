<?php declare(strict_types = 1);
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

class IntervalLiteral implements \SqlFtw\Sql\Expression\Literal
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\TimeInterval|\Dogma\Time\DateTimeInterval|\DateInterval */
    private $value;

    /**
     * @param \SqlFtw\Sql\Expression\TimeInterval|\Dogma\Time\DateTimeInterval|\DateInterval $value
     */
    public function __construct($value)
    {
        Check::types($value, [TimeInterval::class, DateTimeInterval::class, \DateInterval::class]);

        $this->value = $value;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::PARENTHESES);
    }

    /**
     * @return \SqlFtw\Sql\Expression\TimeInterval|\Dogma\Time\DateTimeInterval|\DateInterval
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
