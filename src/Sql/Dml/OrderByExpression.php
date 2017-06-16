<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml;

use Dogma\Check;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Names\ColumnName;
use SqlFtw\Sql\Order;
use SqlFtw\SqlFormatter\SqlFormatter;

class OrderByExpression implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Order */
    private $order;

    /** @var \SqlFtw\Sql\Names\ColumnName|null */
    private $column;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode|null */
    private $expression;

    /** @var int|null */
    private $position;

    public function __construct(Order $order, ?ColumnName $column, ?ExpressionNode $expression = null, ?int $position = null)
    {
        Check::oneOf($column, $expression, $position);

        $this->order = $order;
        $this->column = $column;
        $this->expression = $expression;
        $this->position = $position;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        if ($this->column !== null) {
            $result = $this->column->serialize($formatter);
        } elseif ($this->expression !== null) {
            $result = $this->expression->serialize($formatter);
        } else {
            $result = (string) $this->position;
        }
        if ($this->order !== null) {
            $result .= ' ' . $this->order->serialize($formatter);
        }

        return $result;
    }

}
