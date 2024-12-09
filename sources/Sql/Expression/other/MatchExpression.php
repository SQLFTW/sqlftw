<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;

/**
 * MATCH x AGAINST y
 */
class MatchExpression implements RootNode
{

    /** @var non-empty-list<ColumnIdentifier> */
    public array $columns;

    public RootNode $query;

    public ?MatchMode $mode;

    public bool $queryExpansion;

    /**
     * @param non-empty-list<ColumnIdentifier> $columns
     */
    public function __construct(array $columns, RootNode $query, ?MatchMode $mode, bool $queryExpansion = false)
    {
        $this->columns = $columns;
        $this->query = $query;
        $this->mode = $mode;
        $this->queryExpansion = $queryExpansion;
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
