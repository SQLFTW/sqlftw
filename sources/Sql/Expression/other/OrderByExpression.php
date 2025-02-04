<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Order;
use function is_string;

/**
 * {col_name | expr | position} [ASC | DESC]
 */
class OrderByExpression extends ArgumentValue
{

    public ?Order $order;

    /** @var string|ColumnIdentifier|null */
    public $column;

    public ?RootNode $expression;

    public ?int $position;

    public ?Collation $collation;

    /**
     * @param string|ColumnIdentifier|null $column
     */
    public function __construct(
        ?Order $order,
        $column,
        ?RootNode $expression = null,
        ?int $position = null,
        ?Collation $collation = null
    )
    {
        if (($column !== null) + ($expression !== null) + ($position !== null) !== 1) { // @phpstan-ignore-line
            throw new InvalidDefinitionException('Exactly one of column, expression or position should be set.');
        }

        $this->order = $order;
        $this->column = $column;
        $this->expression = $expression;
        $this->position = $position;
        $this->collation = $collation;
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
