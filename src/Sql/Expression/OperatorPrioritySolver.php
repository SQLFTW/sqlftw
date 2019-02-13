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

class OperatorPrioritySolver
{
    use StrictBehaviorMixin;

    /**
     * priority:
     *   ^
     *   *, /, DIV, %, MOD
     *   -, +
     *   <<, >>
     *   &
     *   |
     */
    public function orderArithmeticOperators(ExpressionNode $node): ExpressionNode
    {
        // todo: operator priority

        return $node;
    }

    /**
     * priority:
     *   NOT
     *   AND, &&
     *   XOR
     *   OR, ||
     */
    public function orderLogicOperators(ExpressionNode $node): ExpressionNode
    {
        // todo: operator priority

        return $node;
    }

}
