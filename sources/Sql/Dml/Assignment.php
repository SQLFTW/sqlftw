<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\SqlSerializable;
use function get_class;
use function gettype;
use function is_object;
use function is_scalar;
use function ucfirst;

class Assignment implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $variable;

    /** @var bool|int|float|string|ExpressionNode */
    private $expression;

    /**
     * @param bool|int|float|string|ExpressionNode|mixed $expression
     */
    public function __construct(QualifiedName $variable, $expression)
    {
        if (!$expression instanceof ExpressionNode && !is_scalar($expression)) {
            $given = is_object($expression) ? get_class($expression) : ucfirst(gettype($expression));
            throw new InvalidDefinitionException("ExpressionNode assigned to variable must be a scalar value or an ExpressionNode. $given given.");
        }
        $this->variable = $variable;
        $this->expression = $expression;
    }

    public function getVariable(): QualifiedName
    {
        return $this->variable;
    }

    /**
     * @return bool|int|float|string|ExpressionNode
     */
    public function getExpression()
    {
        return $this->expression;
    }

    public function serialize(Formatter $formatter): string
    {
        $value = $this->expression instanceof ExpressionNode
            ? $this->expression->serialize($formatter)
            : $formatter->formatValue($this->expression);

        return $this->variable->serialize($formatter) . ' = ' . $value;
    }

}
