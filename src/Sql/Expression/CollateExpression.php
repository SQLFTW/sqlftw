<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Collation;

class CollateExpression implements \SqlFtw\Sql\Expression\ExpressionNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Collation */
    private $collation;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $expression;

    public function __construct(ExpressionNode $expression, Collation $collation)
    {
        $this->expression = $expression;
        $this->collation = $collation;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::CURLY_EXPRESSION);
    }

    public function getCollation(): Collation
    {
        return $this->collation;
    }

    public function getExpression(): ExpressionNode
    {
        return $this->expression;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->expression->serialize($formatter) . ' COLLATE ' . $this->collation->serialize($formatter);
    }

}
