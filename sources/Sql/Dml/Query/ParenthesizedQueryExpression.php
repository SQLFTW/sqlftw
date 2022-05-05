<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\OrderByExpression;

class ParenthesizedQueryExpression implements Query
{

    /** @var Query */
    private $query;

    /** @var OrderByExpression[]|null */
    private $orderBy;

    /** @var int|null */
    private $limit;

    /** @var SelectInto|null */
    private $into;

    /**
     * @param OrderByExpression[]|null $orderBy
     */
    public function __construct(
        Query $query,
        ?array $orderBy = null,
        ?int $limit = null,
        ?SelectInto $into = null
    )
    {
        $this->query = $query;
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->into = $into;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @return OrderByExpression[]|null
     */
    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getInto(): ?SelectInto
    {
        return $this->into;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '(' . $this->query->serialize($formatter) . ')';

        if ($this->orderBy !== null) {
            $result .= "\nORDER BY " . $formatter->formatSerializablesList($this->orderBy, ",\n\t");
        }
        if ($this->limit !== null) {
            $result .= "\nLIMIT " . $this->limit;
        }
        if ($this->into !== null) {
            $result .= ' ' . $this->into->serialize($formatter);
        }

        return $result;
    }

}