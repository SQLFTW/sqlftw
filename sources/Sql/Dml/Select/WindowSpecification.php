<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Select;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\OrderByExpression;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\SqlSerializable;
use function array_map;
use function implode;

class WindowSpecification implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var string|null */
    private $reference;

    /** @var ExpressionNode[]|null */
    private $partitionBy;

    /** @var OrderByExpression[]|null */
    private $orderBy;

    /** @var WindowFrame|null */
    private $frame;

    /**
     * @param string|null $reference
     * @param ExpressionNode[]|null $partitionBy
     * @param OrderByExpression[]|null $orderBy
     * @param WindowFrame|null $frame
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
     * @return ExpressionNode[]|null
     */
    public function getPartitionBy(): ?array
    {
        return $this->partitionBy;
    }

    /**
     * @return OrderByExpression[]|null
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
            $parts[] = 'PARTITION BY ' . implode(', ', array_map(static function (ExpressionNode $expression) use ($formatter): string {
                return $expression->serialize($formatter);
            }, $this->partitionBy));
        }
        if ($this->orderBy !== null) {
            $parts[] = 'ORDER BY ' . implode(', ', array_map(static function (OrderByExpression $expression) use ($formatter): string {
                return $expression->serialize($formatter);
            }, $this->orderBy));
        }
        if ($this->frame !== null) {
            $parts[] = $this->frame->serialize($formatter);
        }

        return '(' . implode(' ', $parts) . ')';
    }

}
