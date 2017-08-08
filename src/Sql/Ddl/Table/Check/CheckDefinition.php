<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Check;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;

class CheckDefinition implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
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
        return 'CHECK (' . $this->expression->serialize($formatter) . ')';
    }

}
