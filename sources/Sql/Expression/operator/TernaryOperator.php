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

/**
 * left BETWEEN middle AND right
 * left LIKE middle ESCAPE right
 */
class TernaryOperator implements OperatorExpression
{

    public ExpressionNode $left;

    public Operator $leftOperator;

    public ExpressionNode $middle;

    public Operator $rightOperator;

    public ExpressionNode $right;

    public function __construct(
        ExpressionNode $left,
        Operator $leftOperator,
        ExpressionNode $middle,
        Operator $rightOperator,
        ExpressionNode $right
    ) {
        Operator::checkTernary($leftOperator, $rightOperator);

        $this->left = $left;
        $this->leftOperator = $leftOperator;
        $this->middle = $middle;
        $this->rightOperator = $rightOperator;
        $this->right = $right;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->left->serialize($formatter) . ' ' . $this->leftOperator->serialize($formatter) . ' ' .
            $this->middle->serialize($formatter) . ' ' . $this->rightOperator->serialize($formatter) . ' ' .
            $this->right->serialize($formatter);
    }

}
