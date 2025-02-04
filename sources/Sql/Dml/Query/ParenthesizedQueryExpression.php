<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\NodeInterface;

class ParenthesizedQueryExpression extends Query
{

    public Query $query;

    public ?WithClause $with;

    /**
     * @param non-empty-list<OrderByExpression>|null $orderBy
     * @param int|SimpleName|Placeholder|null $limit
     * @param int|SimpleName|Placeholder|null $offset
     */
    public function __construct(
        Query $query,
        ?WithClause $with = null,
        ?array $orderBy = null,
        $limit = null,
        $offset = null,
        ?SelectInto $into = null
    )
    {
        $this->query = $query;
        $this->with = $with;
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->into = $into;
    }

    public function containsInto(): bool
    {
        if ($this->into !== null) {
            return true;
        } elseif ($this->query instanceof self && $this->query->containsInto()) {
            return true;
        } elseif (!$this->query instanceof self && $this->query->into !== null) {
            return true;
        } else {
            return false;
        }
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->with !== null) {
            $result .= $this->with->serialize($formatter);
        }

        $result .= '(' . $this->query->serialize($formatter) . ')';

        if ($this->orderBy !== null) {
            $result .= "\nORDER BY " . $formatter->formatNodesList($this->orderBy, ",\n\t");
        }
        if ($this->limit !== null) {
            $result .= "\nLIMIT " . ($this->limit instanceof NodeInterface ? $this->limit->serialize($formatter) : $this->limit);
            if ($this->offset !== null) {
                $result .= " OFFSET " . ($this->offset instanceof NodeInterface ? $this->offset->serialize($formatter) : $this->offset);
            }
        }
        if ($this->into !== null) {
            $result .= ' ' . $this->into->serialize($formatter);
        }

        return $result;
    }

}
