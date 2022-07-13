<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\TableReference\TableReferenceNode;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Statement;

class SelectCommand extends Statement implements SimpleQuery
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<SelectExpression> */
    private $columns;

    /** @var TableReferenceNode|null */
    private $from;

    /** @var ExpressionNode|null */
    private $where;

    /** @var non-empty-array<GroupByExpression>|null */
    private $groupBy;

    /** @var ExpressionNode|null */
    private $having;

    /** @var WithClause|null */
    private $with;

    /** @var non-empty-array<WindowSpecification>|null */
    private $windows;

    /** @var non-empty-array<OrderByExpression>|null */
    private $orderBy;

    /** @var int|SimpleName|null */
    private $limit;

    /** @var int|SimpleName|null */
    private $offset;

    /** @var SelectDistinctOption|null */
    private $distinct;

    /** @var bool[] */
    private $options;

    /** @var SelectInto|null */
    private $into;

    /** @var non-empty-array<SelectLocking>|null */
    private $locking;

    /** @var bool */
    private $withRollup;

    /**
     * @param non-empty-array<SelectExpression> $columns
     * @param non-empty-array<GroupByExpression>|null $groupBy
     * @param non-empty-array<WindowSpecification>|null $windows ($name => $spec)
     * @param non-empty-array<OrderByExpression>|null $orderBy
     * @param int|SimpleName|Placeholder|null $limit
     * @param int|SimpleName|Placeholder|null $offset
     * @param array<string, bool> $options
     * @param non-empty-array<SelectLocking>|null $locking
     */
    public function __construct(
        array $columns,
        ?TableReferenceNode $from,
        ?ExpressionNode $where = null,
        ?array $groupBy = null,
        ?ExpressionNode $having = null,
        ?WithClause $with = null,
        ?array $windows = null,
        ?array $orderBy = null,
        $limit = null,
        $offset = null,
        ?SelectDistinctOption $distinct = null,
        array $options = [],
        ?SelectInto $into = null,
        ?array $locking = null,
        bool $withRollup = false
    ) {
        if ($groupBy === null && $withRollup === true) {
            throw new InvalidDefinitionException('WITH ROLLUP can be used only with GROUP BY.');
        }
        foreach ($options as $option => $value) {
            SelectOption::get($option);
        }

        $this->columns = $columns;
        $this->from = $from;
        $this->where = $where;
        $this->groupBy = $groupBy;
        $this->having = $having;
        $this->with = $with;
        $this->windows = $windows;
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->distinct = $distinct;
        $this->options = $options;
        $this->into = $into;
        $this->locking = $locking;
        $this->withRollup = $withRollup;
    }

    /**
     * @return non-empty-array<SelectExpression>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFrom(): ?TableReferenceNode
    {
        return $this->from;
    }

    public function getWhere(): ?ExpressionNode
    {
        return $this->where;
    }

    /**
     * @return non-empty-array<GroupByExpression>|null
     */
    public function getGroupBy(): ?array
    {
        return $this->groupBy;
    }

    public function withRollup(): bool
    {
        return $this->withRollup;
    }

    public function getHaving(): ?ExpressionNode
    {
        return $this->having;
    }

    public function getWith(): ?WithClause
    {
        return $this->with;
    }

    /**
     * @return non-empty-array<WindowSpecification>|null
     */
    public function getWindows(): ?array
    {
        return $this->windows;
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

    /**
     * @return int|SimpleName|null
     */
    public function getOffset()
    {
        return $this->offset;
    }

    public function getDistinct(): ?SelectDistinctOption
    {
        return $this->distinct;
    }

    /**
     * @return bool[]
     */
    public function getOptions(): array
    {
        return $this->options;
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

    /**
     * @return non-empty-array<SelectLocking>|null
     */
    public function getLocking(): ?array
    {
        return $this->locking;
    }

    public function removeLocking(): self
    {
        $that = clone $this;
        $that->locking = null;

        return $that;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->with !== null) {
            $result .= $this->with->serialize($formatter) . "\n";
        }

        $result .= 'SELECT';
        if ($this->distinct !== null) {
            $result .= ' ' . $this->distinct->serialize($formatter);
        }
        foreach ($this->options as $option => $value) {
            if ($value) {
                $result .= ' ' . $option;
            }
        }

        $result .= ' ' . $formatter->formatSerializablesList($this->columns);

        if ($this->from !== null) {
            $result .= "\nFROM " . $this->from->serialize($formatter);
        }
        if ($this->where !== null) {
            $result .= "\nWHERE " . $this->where->serialize($formatter);
        }
        if ($this->groupBy !== null) {
            $result .= "\nGROUP BY " . $formatter->formatSerializablesList($this->groupBy, ",\n\t");
            if ($this->withRollup) {
                $result .= "\n\tWITH ROLLUP";
            }
        }
        if ($this->having !== null) {
            $result .= "\nHAVING " . $this->having->serialize($formatter);
        }
        if ($this->windows !== null) {
            $result .= "\nWINDOW ";
            $first = true;
            foreach ($this->windows as $name => $window) {
                if (!$first) {
                    $result .= "\n\t";
                }
                $result .= $formatter->formatName($name) . ' AS ' . $window->serialize($formatter);
                $first = false;
            }
        }
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
        if ($this->locking !== null) {
            foreach ($this->locking as $locking) {
                $result .= "\n" . $locking->serialize($formatter);
            }
        }

        return $result;
    }

}
