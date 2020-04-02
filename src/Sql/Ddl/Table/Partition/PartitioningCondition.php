<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Partition;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\SqlSerializable;

class PartitioningCondition implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var PartitioningConditionType */
    private $type;

    /** @var ExpressionNode|null */
    private $expression;

    /** @var string[]|null */
    private $columns;

    /** @var int|null */
    private $algorithm;

    /**
     * @param PartitioningConditionType $type
     * @param ExpressionNode|null $expression
     * @param string[]|null $columns
     * @param int|null $algorithm
     */
    public function __construct(
        PartitioningConditionType $type,
        ?ExpressionNode $expression,
        ?array $columns = null,
        ?int $algorithm = null
    ) {
        $this->type = $type;
        $this->expression = $expression;
        $this->columns = $columns;
        $this->algorithm = $algorithm;
    }

    public function getType(): PartitioningConditionType
    {
        return $this->type;
    }

    public function getExpression(): ?ExpressionNode
    {
        return $this->expression;
    }

    /**
     * @return string[]|null
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    public function getAlgorithm(): ?int
    {
        return $this->algorithm;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->type->serialize($formatter);
        if ($this->expression !== null) {
            $result .= '(' . $this->expression->serialize($formatter) . ')';
        }
        if ($this->algorithm !== null) {
            $result .= ' ALGORITHM = ' . $this->algorithm . ' ';
        }
        if ($this->columns !== null) {
            if ($this->type->equals(PartitioningConditionType::RANGE) || $this->type->equals(PartitioningConditionType::LIST)) {
                $result .= ' COLUMNS';
            }
            $result .= '(' . $formatter->formatNamesList($this->columns) . ')';
        }

        return $result;
    }

}
