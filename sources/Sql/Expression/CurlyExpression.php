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
 * {identifier expr}
 */
class CurlyExpression implements ExpressionNode
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var ExpressionNode */
    private $expression;

    public function __construct(string $name, ExpressionNode $expression)
    {
        $this->name = $name;
        $this->expression = $expression;
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
