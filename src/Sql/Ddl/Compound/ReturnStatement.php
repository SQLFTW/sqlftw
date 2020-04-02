<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;

class ReturnStatement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode */
    private $expression;

    public function __construct(ExpressionNode $expression)
    {
        $this->expression = $expression;
    }

    public function getExpression(): ExpressionNode
    {
        return $this->expression;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'RETURN ' . $this->expression->serialize($formatter);
    }

}
