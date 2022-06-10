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
 * expression AS name
 */
class AliasExpression implements ArgumentNode
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode */
    private $expression;

    /** @var string */
    private $name;

    public function __construct(ExpressionNode $expression, string $name)
    {
        $this->expression = $expression;
        $this->name = $name;
    }

    public function getExpression(): ExpressionNode
    {
        return $this->expression;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->expression->serialize($formatter) . ' AS ' . $formatter->formatName($this->name);
    }

}
