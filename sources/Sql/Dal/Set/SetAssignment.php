<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Set;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Scope;
use SqlFtw\Sql\SqlSerializable;
use function get_class;
use function gettype;
use function is_float;
use function is_int;
use function is_object;
use function is_scalar;
use function sprintf;
use function str_replace;
use function ucfirst;

class SetAssignment implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var Scope */
    private $scope;

    /** @var string */
    private $variable;

    /** @var bool|int|float|string|ExpressionNode */
    private $expression;

    /**
     * @param bool|int|float|string|ExpressionNode|mixed $expression
     */
    public function __construct(string $variable, $expression, ?Scope $scope = null)
    {
        if (!$expression instanceof ExpressionNode && !is_scalar($expression)) {
            throw new InvalidDefinitionException(sprintf(
                'ExpressionNode assigned to variable must be a scalar value or an ExpressionNode. %s given.',
                is_object($expression) ? get_class($expression) : ucfirst(gettype($expression))
            ));
        }
        if ($scope === null) {
            $scope = Scope::get(Scope::DEFAULT);
        }
        $this->scope = $scope;
        $this->variable = $variable;
        $this->expression = $expression;
    }

    public function getScope(): Scope
    {
        return $this->scope;
    }

    public function getVariable(): string
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
        $scope = $this->scope->serialize($formatter);
        $scope .= $scope !== '' ? ' ' : '';

        return $scope . $this->variable . ' = ' . $this->formatExpression($formatter, $this->expression);
    }

    /**
     * @param bool|int|float|string|ExpressionNode $expression
     */
    private function formatExpression(Formatter $formatter, $expression): string
    {
        if ($expression === true) {
            return 'ON';
        } elseif ($expression === false) {
            return 'OFF';
        } elseif (is_int($expression) || is_float($expression)) {
            return (string) $expression;
        } elseif ($expression instanceof ExpressionNode) {
            return $expression->serialize($formatter);
        } else {
            return str_replace('\'', '\'\'', $expression);
        }
    }

}
