<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Sql\NodeType;
use SqlFtw\SqlFormatter\SqlFormatter;

class Literal implements \SqlFtw\Sql\Expression\ExpressionNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var null|bool|int|float|string|\DateTimeInterface|\Dogma\Time\Date|\Dogma\Time\Time|\Dogma\Time\DateTimeInterval|\SqlFtw\Sql\Time\TimeInterval */
    private $value;

    /** @var null|bool|int|float|string|\DateTimeInterface|\Dogma\Time\Date|\Dogma\Time\Time|\Dogma\Time\DateTimeInterval|\SqlFtw\Sql\Time\TimeInterval */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::LITERAL);
    }

    /**
     * @return null|bool|int|float|string|\DateTimeInterface|\Dogma\Time\Date|\Dogma\Time\Time|\Dogma\Time\DateTimeInterval|\SqlFtw\Sql\Time\TimeInterval
     */
    public function getValue()
    {
        return $this->value;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return $formatter->formatValue($this->value);
    }

}
