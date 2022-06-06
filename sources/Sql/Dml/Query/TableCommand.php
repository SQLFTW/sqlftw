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
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\SimpleName;

class TableCommand implements SimpleQuery
{

    /** @var QualifiedName */
    private $table;

    /** @var non-empty-array<OrderByExpression>|null */
    private $orderBy;

    /** @var int|SimpleName|null */
    private $limit;

    /** @var int|SimpleName|null */
    private $offset;

    /** @var SelectInto|null */
    private $into;

    /**
     * @param non-empty-array<OrderByExpression>|null $orderBy
     * @param int|SimpleName|null $limit
     * @param int|SimpleName|null $offset
     */
    public function __construct(
        QualifiedName $table,
        ?array $orderBy = null,
        $limit = null,
        $offset = null,
        ?SelectInto $into = null
    )
    {
        $this->table = $table;
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->into = $into;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    /**
     * @return non-empty-array<OrderByExpression>|null
     */
    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    public function removeOrderBy(): SimpleQuery
    {
        $that = clone $this;
        $that->orderBy = null;

        return $that;
    }

    /**
     * @return int|SimpleName|null
     */
    public function getLimit()
    {
        return $this->limit;
    }

    public function removeLimit(): SimpleQuery
    {
        $that = clone $this;
        $that->limit = null;

        return $that;
    }

    /**
     * @return int|SimpleName|null
     */
    public function getOffset()
    {
        return $this->offset;
    }

    public function getInto(): ?SelectInto
    {
        return $this->into;
    }

    public function removeInto(): SimpleQuery
    {
        $that = clone $this;
        $that->into = null;

        return $that;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = "TABLE " . $this->table->serialize($formatter);

        if ($this->orderBy !== null) {
            $result .= "\nORDER BY " . $formatter->formatSerializablesList($this->orderBy, ",\n\t");
        }

        if ($this->limit !== null) {
            $result .= "\nLIMIT " . ($this->limit instanceof SimpleName ? $this->limit->serialize($formatter) : $this->limit);
            if ($this->offset !== null) {
                $result .= "\nOFFSET " . ($this->offset instanceof SimpleName ? $this->offset->serialize($formatter) : $this->offset);
            }
        }

        if ($this->into !== null) {
            $result .= "\n" . $this->into->serialize($formatter);
        }

        return $result;
    }

}
