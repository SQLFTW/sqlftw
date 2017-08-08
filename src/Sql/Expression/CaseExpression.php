<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\Check;
use SqlFtw\Formatter\Formatter;

class CaseExpression implements \SqlFtw\Sql\Statement
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\Literal|null */
    private $condition;

    /** @var \SqlFtw\Sql\Expression\Literal[]|\SqlFtw\Sql\Expression\ExpressionNode[] */
    private $values;

    /** @var \SqlFtw\Sql\Expression\Literal[] */
    private $results;

    /**
     * @param \SqlFtw\Sql\Expression\Literal|null $condition
     * @param \SqlFtw\Sql\Expression\Literal[]|\SqlFtw\Sql\Expression\ExpressionNode[] $values
     * @param \SqlFtw\Sql\Expression\Literal[] $results
     */
    public function __construct(?ExpressionNode $condition, array $values, array $results)
    {
        Check::array($values, 1);
        if ($condition !== null) {
            Check::itemsOfType($values, Literal::class);
        } else {
            Check::itemsOfType($values, ExpressionNode::class);
        }
        Check::array($results, count($values), count($values) + 1);
        Check::itemsOfType($results, ValueLiteral::class);

        $this->condition = $condition;
        $this->values = $values;
        $this->results = $results;
    }

    public function getCondition(): ?ExpressionNode
    {
        return $this->condition;
    }

    /**
     * @return \SqlFtw\Sql\Expression\Literal[]|\SqlFtw\Sql\Expression\ExpressionNode[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return \SqlFtw\Sql\Expression\Literal[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CASE';
        if ($this->condition !== null) {
            $result .= ' ' . $this->condition->serialize($formatter);
        }
        foreach ($this->values as $i => $condition) {
            $result = ' WHEN ' . $this->values[$i]->serialize($formatter) . ' THAN ' . $this->results[$i]->serialize($formatter);
        }
        if (count($this->values) < count($this->results)) {
            $result .= ' ELSE ' . $this->results[count($this->values)]->serialize($formatter);
        }
        $result .= ' END';

        return $result;
    }

}
