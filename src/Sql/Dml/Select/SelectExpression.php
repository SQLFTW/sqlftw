<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Select;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;

class SelectExpression implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $expression;

    /** @var string|null */
    private $alias;

    public function __construct(ExpressionNode $expression, ?string $alias = null)
    {
        $this->expression = $expression;
        $this->alias = $alias;
    }

    public function getExpression(): ExpressionNode
    {
        return $this->expression;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->expression->serialize($formatter);
        if ($this->alias !== null) {
            $result .= ' AS ' . $formatter->formatName($this->alias);
        }

        return $result;
    }

}
