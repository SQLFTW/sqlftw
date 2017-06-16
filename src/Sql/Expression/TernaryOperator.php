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
use SqlFtw\Sql\Operator;
use SqlFtw\SqlFormatter\SqlFormatter;

/**
 * left BETWEEN middle AND right
 * left LIKE middle ESCAPE right
 */
class TernaryOperator implements \SqlFtw\Sql\Expression\ExpressionNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $left;

    /** @var string */
    private $leftOperator;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $middle;

    /** @var string */
    private $rightOperator;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $right;

    public function __construct(
        ExpressionNode $left,
        string $leftOperator,
        ExpressionNode $middle,
        string $rightOperator,
        ExpressionNode $right
    ) {
        Operator::get($leftOperator)->checkTernaryLeft();
        Operator::get($rightOperator)->checkTernaryRight();

        $this->left = $left;
        $this->leftOperator = $leftOperator;
        $this->middle = $middle;
        $this->rightOperator = $rightOperator;
        $this->right = $right;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::TERNARY_OPERATOR);
    }

    public function getLeft(): ExpressionNode
    {
        return $this->left;
    }

    public function getLeftOperator(): string
    {
        return $this->leftOperator;
    }

    public function getMiddle(): ExpressionNode
    {
        return $this->middle;
    }

    public function getRightOperator(): string
    {
        return $this->rightOperator;
    }

    public function getRight(): ExpressionNode
    {
        return $this->right;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return $this->left->serialize($formatter) . ' ' . $this->leftOperator . ' ' .
            $this->middle->serialize($formatter) . ' ' . $this->rightOperator . ' ' .
            $this->right->serialize($formatter);
    }

}
