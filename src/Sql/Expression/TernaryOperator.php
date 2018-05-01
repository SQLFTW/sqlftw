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

/**
 * left BETWEEN middle AND right
 * left LIKE middle ESCAPE right
 */
class TernaryOperator implements ExpressionNode
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $left;

    /** @var string[] */
    private $leftOperator;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $middle;

    /** @var string */
    private $rightOperator;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $right;

    /**
     * @param \SqlFtw\Sql\Expression\ExpressionNode $left
     * @param string|string[] $leftOperator
     * @param \SqlFtw\Sql\Expression\ExpressionNode $middle
     * @param string $rightOperator
     * @param \SqlFtw\Sql\Expression\ExpressionNode $right
     */
    public function __construct(
        ExpressionNode $left,
        $leftOperator,
        ExpressionNode $middle,
        string $rightOperator,
        ExpressionNode $right
    ) {
        if (is_array($leftOperator)) {
            foreach ($leftOperator as $op) {
                Operator::get($op)->checkTernaryLeft();
            }
        } else {
            Operator::get($leftOperator)->checkTernaryLeft();
            $leftOperator = [$leftOperator];
        }
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

    /**
     * @return string[]
     */
    public function getLeftOperator(): array
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

    public function serialize(Formatter $formatter): string
    {
        return $this->left->serialize($formatter) . ' ' . implode(' ', $this->leftOperator) . ' ' .
            $this->middle->serialize($formatter) . ' ' . $this->rightOperator . ' ' .
            $this->right->serialize($formatter);
    }

}
