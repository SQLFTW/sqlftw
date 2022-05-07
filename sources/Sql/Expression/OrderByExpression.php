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
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\ColumnName;
use SqlFtw\Sql\Order;
use SqlFtw\Sql\QualifiedName;
use function is_string;

/**
 * {col_name | expr | position} [ASC | DESC]
 */
class OrderByExpression implements ExpressionNode
{
    use StrictBehaviorMixin;

    /** @var Order|null */
    private $order;

    /** @var string|QualifiedName|ColumnName|null */
    private $column;

    /** @var ExpressionNode|null */
    private $expression;

    /** @var int|null */
    private $position;

    /** @var Collation|null */
    private $collation;

    /**
     * @param string|QualifiedName|ColumnName|null $column
     */
    public function __construct(
        ?Order $order,
        $column,
        ?ExpressionNode $expression = null,
        ?int $position = null,
        ?Collation $collation = null
    )
    {
        Check::oneOf($column, $expression, $position);

        $this->order = $order;
        $this->column = $column;
        $this->expression = $expression;
        $this->position = $position;
        $this->collation = $collation;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    /**
     * @return string|ColumnName|QualifiedName|null
     */
    public function getColumn()
    {
        return $this->column;
    }

    public function getExpression(): ?ExpressionNode
    {
        return $this->expression;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getCollation(): ?Collation
    {
        return $this->collation;
    }

    public function serialize(Formatter $formatter): string
    {
        if (is_string($this->column)) {
            $result = $formatter->formatName($this->column);
        } elseif ($this->column !== null) {
            $result = $this->column->serialize($formatter);
        } elseif ($this->expression !== null) {
            $result = $this->expression->serialize($formatter);
        } else {
            $result = (string) $this->position;
        }
        if ($this->order !== null) {
            $result .= ' ' . $this->order->serialize($formatter);
        }
        if ($this->collation !== null) {
            $result .= ' COLLATE ' . $this->collation->serialize($formatter);
        }

        return $result;
    }

}
