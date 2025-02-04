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
 * expression AS name
 */
class AliasExpression extends ArgumentNode
{

    public ExpressionNode $expression;

    public string $alias;

    public function __construct(ExpressionNode $expression, string $alias)
    {
        $this->expression = $expression;
        $this->alias = $alias;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->expression->serialize($formatter) . ' AS ' . $formatter->formatName($this->alias);
    }

}
