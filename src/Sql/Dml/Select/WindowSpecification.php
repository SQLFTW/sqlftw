<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Select;

use function array_map;
use Dogma\StrictBehaviorMixin;
use function func_get_args;
use function implode;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\OrderByExpression;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\SqlSerializable;

class WindowSpecification implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var string|null */
    private $reference;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode[]|null */
    private $partitionBy;

    /** @var \SqlFtw\Sql\Dml\OrderByExpression[]|null */
    private $orderBy;

    /** @var \SqlFtw\Sql\Dml\Select\WindowFrame|null */
    private $frame;

    /**
     * @param string|null $reference
     * @param \SqlFtw\Sql\Expression\ExpressionNode[]|null $partitionBy
     * @param \SqlFtw\Sql\Dml\OrderByExpression[]|null $orderBy
     * @param \SqlFtw\Sql\Dml\Select\WindowFrame|null $frame
     */
    public function __construct(?string $reference, ?array $partitionBy, ?array $orderBy, ?WindowFrame $frame)
    {
        $this->reference = $reference;
        $this->partitionBy = $partitionBy;
        $this->orderBy = $orderBy;
        $this->frame = $frame;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * @return \SqlFtw\Sql\Expression\ExpressionNode[]|null
     */
    public function getPartitionBy(): ?array
    {
        return $this->partitionBy;
    }

    /**
     * @return \SqlFtw\Sql\Dml\OrderByExpression[]|null
     */
    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    public function getFrame(): ?WindowFrame
    {
        return $this->frame;
    }

    public function serialize(Formatter $formatter): string
    {
        $parts = [];
        if ($this->reference !== null) {
            $parts[] = $formatter->formatName($this->reference);
        }
        if ($this->partitionBy !== null) {
            $parts[] = 'PARTITION BY ' . implode(', ', array_map(function (ExpressionNode $expression) use ($formatter): string {
                return $expression->serialize($formatter);
            }, $this->partitionBy));
        }
        if ($this->orderBy !== null) {
            $parts[] = 'ORDER BY ' . implode(', ', array_map(function (OrderByExpression $expression) use ($formatter): string {
                return $expression->serialize($formatter);
            }, $this->orderBy));
        }
        if ($this->frame !== null) {
            $parts[] = $this->frame->serialize($formatter);
        }

        return '(' . implode(' ', $parts) . ')';
    }

}
