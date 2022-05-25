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
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Statement;
use function count;

/**
 * CASE x THEN y ELSE z END
 */
class CaseExpression implements Statement, RootNode
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode|null */
    private $condition;

    /** @var non-empty-array<ExpressionNode> */
    private $values;

    /** @var non-empty-array<ExpressionNode> */
    private $results;

    /**
     * @param non-empty-array<ExpressionNode> $values
     * @param non-empty-array<ExpressionNode> $results
     */
    public function __construct(?ExpressionNode $condition, array $values, array $results)
    {
        if (count($results) < count($values) || count($results) > count($values) + 1) {
            throw new InvalidDefinitionException('Count of results should be same or one higher then count of values.');
        }

        $this->condition = $condition;
        $this->values = $values;
        $this->results = $results;
    }

    public function getCondition(): ?ExpressionNode
    {
        return $this->condition;
    }

    /**
     * @return non-empty-array<ExpressionNode>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return non-empty-array<ExpressionNode>
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