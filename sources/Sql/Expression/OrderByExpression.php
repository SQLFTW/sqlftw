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
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\ColumnName;
use SqlFtw\Sql\Order;

class OrderByExpression implements ExpressionNode
{
    use StrictBehaviorMixin;

    /** @var Order|null */
    private $order;

    /** @var ColumnName|null */
    private $column;

    /** @var ExpressionNode|null */
    private $expression;

    /** @var int|null */
    private $position;

    public function __construct(?Order $order, ?ColumnName $column, ?ExpressionNode $expression = null, ?int $position = null)
    {
        Check::oneOf($column, $expression, $position);

        $this->order = $order;
        $this->column = $column;
        $this->expression = $expression;
        $this->position = $position;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::ORDER_BY_EXPRESSION);
    }

    public function serialize(Formatter $formatter): string
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
