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
use SqlFtw\Sql\ColumnName;

class MatchExpression implements \SqlFtw\Sql\Expression\ExpressionNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\ColumnName[] */
    private $columns;

    /** @var string */
    private $query;

    /** @var \SqlFtw\Sql\Expression\MatchMode|null */
    private $mode;

    /** @var bool */
    private $queryExpansion;

    /**
     * @param \SqlFtw\Sql\ColumnName[] $columns
     * @param string $query
     * @param \SqlFtw\Sql\Expression\MatchMode|null $mode
     */
    public function __construct(array $columns, string $query, ?MatchMode $mode, bool $queryExpansion = false)
    {
        Check::itemsOfType($columns, ColumnName::class);

        $this->columns = $columns;
        $this->query = $query;
        $this->mode = $mode;
        $this->queryExpansion = $queryExpansion;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::MATCH_EXPRESSION);
    }

    /**
     * @return \SqlFtw\Sql\ColumnName[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getQuery(): string
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
            . ') AGAINST(' . $formatter->formatString($this->query);

        if ($this->mode !== null) {
            $result .= ' IN ' . $this->mode->serialize($formatter);
        }
        if ($this->queryExpansion) {
            $result .= ' WITH QUERY EXPANSION';
        }

        return $result . ')';
    }

}
