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
 * variable := expr
 */
class AssignOperator implements OperatorExpression
{
    use StrictBehaviorMixin;

    /** @var Identifier */
    private $variable;

    /** @var RootNode */
    private $expression;

    public function __construct(Identifier $variable, RootNode $expression)
    {
        $this->variable = $variable;
        $this->expression = $expression;
    }

    public function getVariable(): Identifier
    {
        return $this->variable;
    }

    public function getExpression(): RootNode
    {
        return $this->expression;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->variable->serialize($formatter) . ' := ' . $this->expression->serialize($formatter);
    }

}
