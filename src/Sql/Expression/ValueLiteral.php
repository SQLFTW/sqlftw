<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use DateTimeInterface;
use Dogma\StrictBehaviorMixin;
use Dogma\Time\Date;
use Dogma\Time\Span\DateTimeSpan;
use Dogma\Time\Time;
use SqlFtw\Formatter\Formatter;

class ValueLiteral implements Literal
{
    use StrictBehaviorMixin;

    /** @var bool|int|float|string|DateTimeInterface|Date|Time|DateTimeSpan|TimeInterval */
    private $value;

    /**
     * @param bool|int|float|string|DateTimeInterface|Date|Time|DateTimeSpan|TimeInterval $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::LITERAL);
    }

    /**
     * @return bool|int|float|string|DateTimeInterface|Date|Time|DateTimeSpan|TimeInterval
     */
    public function getValue()
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        return $formatter->formatValue($this->value);
    }

}
