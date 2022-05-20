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
use SqlFtw\Sql\ColumnName;
use function is_string;

/**
 * MATCH x AGAINST y
 */
class MatchExpression implements ExpressionNode
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<ColumnName> */
    private $columns;

    /** @var string|Literal */
    private $query;

    /** @var MatchMode|null */
    private $mode;

    /** @var bool */
    private $queryExpansion;

    /**
     * @param non-empty-array<ColumnName> $columns
     * @param string|Literal $query
     */
    public function __construct(array $columns, $query, ?MatchMode $mode, bool $queryExpansion = false)
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

    /**
     * @return string|Literal
     */
    public function getQuery()
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
            . ') AGAINST(' . is_string($this->query) ? $formatter->formatString($this->query) : $this->query->serialize($formatter);

        if ($this->mode !== null) {
            $result .= ' IN ' . $this->mode->serialize($formatter);
        }
        if ($this->queryExpansion) {
            $result .= ' WITH QUERY EXPANSION';
        }

        return $result . ')';
    }

}
