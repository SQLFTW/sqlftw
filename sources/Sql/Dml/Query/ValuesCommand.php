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
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Statement;
use function array_values;

class ValuesCommand extends Statement implements SimpleQuery
{

    /** @var non-empty-array<Row> */
    private $rows;

    /** @var non-empty-array<OrderByExpression>|null */
    private $orderBy;

    /** @var int|SimpleName|null */
    private $limit;

    /** @var SelectInto|null */
    private $into;

    /**
     * @param non-empty-array<Row> $rows
     * @param non-empty-array<OrderByExpression>|null $orderBy
     * @param int|SimpleName|Placeholder|null $limit
     */
    public function __construct(
        array $rows,
        ?array $orderBy = null,
        $limit = null,
        ?SelectInto $into = null
    )
    {
        $this->rows = array_values($rows);
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->into = $into;
    }

    /**
     * @return non-empty-array<Row>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @return non-empty-array<OrderByExpression>|null
     */
    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    public function removeOrderBy(): Query
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

    public function removeLimit(): Query
    {
        $that = clone $this;
        $that->limit = null;

        return $that;
    }

    public function getInto(): ?SelectInto
    {
        return $this->into;
    }

    public function removeInto(): Query
    {
        $that = clone $this;
        $that->into = null;

        return $that;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = "VALUES \n    ";
        foreach ($this->rows as $i => $row) {
            if ($i !== 0) {
                $result .= ",\n    ";
            }
            $result .= $row->serialize($formatter);
        }

        if ($this->orderBy !== null) {
            $result .= "\nORDER BY " . $formatter->formatSerializablesList($this->orderBy, ",\n\t");
        }

        if ($this->limit !== null) {
            $result .= "\nLIMIT " . ($this->limit instanceof SimpleName ? $this->limit->serialize($formatter) : $this->limit);
        }

        if ($this->into !== null) {
            $result .= "\n" . $this->into->serialize($formatter);
        }

        return $result;
    }

}
