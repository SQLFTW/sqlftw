<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Constraint;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;

class CheckDefinition implements ConstraintBody
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode */
    private $expression;

    /** @var bool|null */
    private $enforced;

    public function __construct(ExpressionNode $expression, ?bool $enforced = null)
    {
        $this->expression = $expression;
        $this->enforced = $enforced;
    }

    public function getExpression(): ExpressionNode
    {
        return $this->expression;
    }

    public function getEnforced(): ?bool
    {
        return $this->enforced;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CHECK (' . $this->expression->serialize($formatter) . ')';

        if ($this->enforced !== null) {
            $result .= ' ' . ($this->enforced ? 'ENFORCED' : 'NOT ENFORCED');
        }

        return $result;
    }

}
