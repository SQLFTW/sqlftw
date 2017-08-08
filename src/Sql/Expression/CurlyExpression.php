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

class CurlyExpression implements \SqlFtw\Sql\Expression\ExpressionNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $expression;

    public function __construct(string $name, ExpressionNode $expression)
    {
        $this->name = $name;
        $this->expression = $expression;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::CURLY_EXPRESSION);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExpression(): ExpressionNode
    {
        return $this->expression;
    }

    public function serialize(Formatter $formatter): string
    {
        return '{' . $formatter->formatName($this->name) . ' ' . $this->expression->serialize($formatter) . '}';
    }

}
