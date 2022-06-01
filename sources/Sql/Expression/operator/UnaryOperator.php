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
 * NOT right
 * !right
 * ~right
 * -right
 * +right
 */
class UnaryOperator implements OperatorExpression
{
    use StrictBehaviorMixin;

    /** @var string */
    private $operator;

    /** @var RootNode */
    private $right;

    public function __construct(string $operator, RootNode $right)
    {
        Operator::get($operator)->checkUnary();

        $this->operator = $operator;
        $this->right = $right;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getRight(): RootNode
    {
        return $this->right;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->operator . ($this->operator === Operator::NOT ? ' ' : '') . $this->right->serialize($formatter);
    }

}
