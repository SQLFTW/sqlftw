<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use function implode;
use function is_array;

/**
 * left := right
 * left AND right
 * left OR right
 * left XOR right
 * left && right
 * left || right
 * left = right
 * left <=> right
 * left != right
 * left <> right
 * left < right
 * left <= right
 * left > right
 * left >= right
 * left + right
 * left - right
 * left * right
 * left / right
 * left DIV right
 * left MOD right
 * left % right
 * left & right
 * left | right
 * left ^ right
 * left << right
 * left >> right
 * left IS right
 * left LIKE right
 * left REGEXP right
 * left RLIKE right
 * left SOUNDS right
 * left IN right
 * left -> right
 * left ->> right
 */
class BinaryOperator implements ExpressionNode
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode */
    private $left;

    /** @var string|string[] */
    private $operator;

    /** @var ExpressionNode */
    private $right;

    /**
     * @param ExpressionNode $left
     * @param string|string[] $operator
     * @param ExpressionNode $right
     */
    public function __construct(
        ExpressionNode $left,
        $operator,
        ExpressionNode $right
    ) {
        if (is_array($operator)) {
            foreach ($operator as $op) {
                Operator::get($op)->checkBinary();
            }
        } else {
            Operator::get($operator)->checkBinary();
        }

        $this->left = $left;
        $this->operator = $operator;
        $this->right = $right;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::BINARY_OPERATOR);
    }

    public function getLeft(): ExpressionNode
    {
        return $this->left;
    }

    /**
     * @return string|string[]
     */
    public function getOperator()
    {
        return $this->operator;
    }

    public function getRight(): ExpressionNode
    {
        return $this->right;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->left->serialize($formatter) . ' '
            . (is_array($this->operator) ? implode('', $this->operator) : $this->operator) . ' '
            . $this->right->serialize($formatter);
    }

}
