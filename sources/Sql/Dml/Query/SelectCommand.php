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
use SqlFtw\Sql\Dml\OptimizerHint\OptimizerHint;
use SqlFtw\Sql\Dml\TableReference\TableReferenceNode;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\NodeInterface;

class SelectCommand extends SimpleQuery
{

    /** @var non-empty-list<SelectExpression> */
    public array $columns;

    public ?TableReferenceNode $from;

    public ?ExpressionNode $where;

    /** @var non-empty-list<GroupByExpression>|null */
    public ?array $groupBy;

    public ?ExpressionNode $having;

    public ?WithClause $with;

    /** @var non-empty-array<string, WindowSpecification>|null */
    public ?array $windows;

    public ?SelectDistinctOption $distinct;

    /** @var array<SelectOption::*, bool> */
    public array $options;

    /** @var non-empty-list<SelectLocking>|null */
    public ?array $locking;

    public bool $withRollup;

    /** @var non-empty-list<OptimizerHint>|null */
    public ?array $optimizerHints;

    /**
     * @param non-empty-list<SelectExpression> $columns
     * @param non-empty-list<GroupByExpression>|null $groupBy
     * @param non-empty-array<string, WindowSpecification>|null $windows ($name => $spec)
     * @param non-empty-list<OrderByExpression>|null $orderBy
     * @param int|SimpleName|Placeholder|null $limit
     * @param int|SimpleName|Placeholder|null $offset
     * @param array<SelectOption::*, bool> $options
     * @param non-empty-list<SelectLocking>|null $locking
     * @param non-empty-list<OptimizerHint>|null $optimizerHints
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
        bool $withRollup = false,
        ?array $optimizerHints = null
    ) {
        if ($groupBy === null && $withRollup === true) {
            throw new InvalidDefinitionException('WITH ROLLUP can be used only with GROUP BY.');
        }
        foreach ($options as $option => $value) {
            SelectOption::checkValue($option);
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
        $this->optimizerHints = $optimizerHints;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->with !== null) {
            $result .= $this->with->serialize($formatter) . "\n";
        }

        $result .= 'SELECT';

        if ($this->optimizerHints !== null) {
            $result .= ' /*+ ' . $formatter->formatNodesList($this->optimizerHints) . ' */';
        }

        if ($this->distinct !== null) {
            $result .= ' ' . $this->distinct->serialize($formatter);
        }
        foreach ($this->options as $option => $value) {
            if ($value) {
                $result .= ' ' . $option;
            }
        }

        $result .= ' ' . $formatter->formatNodesList($this->columns);

        if ($this->into !== null && $this->into->position === SelectInto::POSITION_BEFORE_FROM) {
            $result .= "\n" . $this->into->serialize($formatter);
        }
        if ($this->from !== null) {
            $result .= "\nFROM " . $this->from->serialize($formatter);
        }
        if ($this->where !== null) {
            $result .= "\nWHERE " . $this->where->serialize($formatter);
        }
        if ($this->groupBy !== null) {
            $result .= "\nGROUP BY " . $formatter->formatNodesList($this->groupBy, ",\n\t");
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
            $result .= "\nORDER BY " . $formatter->formatNodesList($this->orderBy, ",\n\t");
        }
        if ($this->limit !== null) {
            $result .= "\nLIMIT " . ($this->limit instanceof NodeInterface ? $this->limit->serialize($formatter) : $this->limit);
            if ($this->offset !== null) {
                $result .= "\nOFFSET " . ($this->offset instanceof NodeInterface ? $this->offset->serialize($formatter) : $this->offset);
            }
        }
        if ($this->into !== null && $this->into->position === SelectInto::POSITION_BEFORE_LOCKING) {
            $result .= "\n" . $this->into->serialize($formatter);
        }
        if ($this->locking !== null) {
            foreach ($this->locking as $locking) {
                $result .= "\n" . $locking->serialize($formatter);
            }
        }
        if ($this->into !== null && $this->into->position === SelectInto::POSITION_AFTER_LOCKING) {
            $result .= "\n" . $this->into->serialize($formatter);
        }

        return $result;
    }

}
