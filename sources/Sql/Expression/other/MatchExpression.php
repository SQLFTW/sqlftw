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

/**
 * MATCH x AGAINST y
 */
class MatchExpression implements RootNode
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<ColumnName> */
    private $columns;

    /** @var ExpressionNode */
    private $query;

    /** @var MatchMode|null */
    private $mode;

    /** @var bool */
    private $queryExpansion;

    /**
     * @param non-empty-array<ColumnName> $columns
     */
    public function __construct(array $columns, ExpressionNode $query, ?MatchMode $mode, bool $queryExpansion = false)
    {
        $this->columns = $columns;
        $this->query = $query;
        $this->mode = $mode;
        $this->queryExpansion = $queryExpansion;
    }

    /**
     * @return non-empty-array<ColumnName>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getQuery(): ExpressionNode
    {
        return $this->query;
    }

    public function getMode(): ?MatchMode
    {
        return $this->mode;
    }

    public function queryExpansion(): bool
    {
        return $this->queryExpansion;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'MATCH(' . $formatter->formatSerializablesList($this->columns)
            . ') AGAINST(' . $this->query->serialize($formatter);

        if ($this->mode !== null) {
            $result .= ' IN ' . $this->mode->serialize($formatter);
        }
        if ($this->queryExpansion) {
            $result .= ' WITH QUERY EXPANSION';
        }

        return $result . ')';
    }

}
