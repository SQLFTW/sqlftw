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
use SqlFtw\Sql\QualifiedName;

class TableCommand implements SimpleQuery
{

    /** @var QualifiedName */
    private $table;

    /** @var OrderByExpression[]|null */
    private $orderBy;

    /** @var int|null */
    private $limit;

    /** @var int|null */
    private $offset;

    /** @var SelectInto|null */
    private $into;

    /**
     * @param OrderByExpression[]|null $orderBy
     */
    public function __construct(
        QualifiedName $table,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
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
     * @return OrderByExpression[]|null
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

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function removeLimit(): SimpleQuery
    {
        $that = clone $this;
        $that->limit = null;

        return $that;
    }

    public function getOffset(): ?int
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
            $result .= "\nLIMIT " . $this->limit;
            if ($this->offset !== null) {
                $result .= "\nOFFSET " . $this->offset;
            }
        }
        if ($this->into !== null) {
            $result .= "\n" . $this->into->serialize($formatter);
        }

        return $result;
    }

}
