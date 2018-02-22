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

/**
 * NOT right
 * !right
 * ~right
 * -right
 * +right
 */
class UnaryOperator implements \SqlFtw\Sql\Expression\ExpressionNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $operator;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $right;

    public function __construct(string $operator, ExpressionNode $right)
    {
        Operator::get($operator)->checkUnary();

        $this->operator = $operator;
        $this->right = $right;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::UNARY_OPERATOR);
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getRight(): ExpressionNode
    {
        return $this->right;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->operator . ($this->operator === Operator::NOT ? ' ' : '') . $this->right->serialize($formatter);
    }

}
