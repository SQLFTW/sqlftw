<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Select;

use Dogma\InvalidTypeException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\SqlSerializable;
use function is_string;

class SelectExpression implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $expression;

    /** @var string|null */
    private $alias;

    /** @var \SqlFtw\Sql\Dml\Select\WindowSpecification|string|null */
    private $window;

    /**
     * @param \SqlFtw\Sql\Expression\ExpressionNode $expression
     * @param string|null $alias
     * @param \SqlFtw\Sql\Dml\Select\WindowSpecification|string|null $window
     */
    public function __construct(ExpressionNode $expression, ?string $alias = null, $window = null)
    {
        if (!is_string($window) && !$window instanceof WindowSpecification) {
            throw new InvalidTypeException(WindowSpecification::class . '|string', $window);
        }
        $this->expression = $expression;
        $this->alias = $alias;
        $this->window = $window;
    }

    public function getExpression(): ExpressionNode
    {
        return $this->expression;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @return \SqlFtw\Sql\Dml\Select\WindowSpecification|string|null
     */
    public function getWindow()
    {
        return $this->window;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->expression->serialize($formatter);

        if (is_string($this->window)) {
            $result .= ' OVER ' . $formatter->formatName($this->window);
        } elseif ($this->window !== null) {
            $result .= ' OVER ' . $this->window->serialize($formatter);
        }
        if ($this->alias !== null) {
            $result .= ' AS ' . $formatter->formatName($this->alias);
        }

        return $result;
    }

}
