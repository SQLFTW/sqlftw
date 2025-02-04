<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Update;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Assignment;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Dml\OptimizerHint\OptimizerHint;
use SqlFtw\Sql\Dml\TableReference\TableReferenceList;
use SqlFtw\Sql\Dml\TableReference\TableReferenceNode;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\NodeInterface;
use function count;

class UpdateCommand extends Command implements DmlCommand
{

    public TableReferenceNode $tableReferences;

    /** @var non-empty-list<Assignment> */
    public array $values;

    public ?RootNode $where;

    public ?WithClause $with;

    /** @var non-empty-list<OrderByExpression>|null */
    public ?array $orderBy;

    /** @var int|SimpleName|Placeholder|null */
    public $limit;

    public bool $ignore;

    public bool $lowPriority;

    /** @var non-empty-list<OptimizerHint>|null */
    public ?array $optimizerHints;

    /**
     * @param non-empty-list<Assignment> $values
     * @param non-empty-list<OrderByExpression>|null $orderBy
     * @param int|SimpleName|Placeholder|null $limit
     * @param non-empty-list<OptimizerHint>|null $optimizerHints
     */
    public function __construct(
        TableReferenceNode $tableReferences,
        array $values,
        ?RootNode $where = null,
        ?WithClause $with = null,
        ?array $orderBy = null,
        $limit = null,
        bool $ignore = false,
        bool $lowPriority = false,
        ?array $optimizerHints = null
    ) {
        if ($tableReferences instanceof TableReferenceList && count($tableReferences) > 1 && ($orderBy !== null || $limit !== null)) {
            throw new InvalidDefinitionException('ORDER BY and LIMIT must not be set, when more table references are used.');
        }

        $this->tableReferences = $tableReferences;
        $this->values = $values;
        $this->where = $where;
        $this->with = $with;
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->ignore = $ignore;
        $this->lowPriority = $lowPriority;
        $this->optimizerHints = $optimizerHints;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->with !== null) {
            $result .= $this->with->serialize($formatter) . "\n";
        }

        $result .= 'UPDATE ';

        if ($this->optimizerHints !== null) {
            $result .= '/*+ ' . $formatter->formatNodesList($this->optimizerHints) . ' */ ';
        }

        if ($this->lowPriority) {
            $result .= 'LOW_PRIORITY ';
        }
        if ($this->ignore) {
            $result .= 'IGNORE ';
        }

        $result .= $this->tableReferences->serialize($formatter);
        $result .= ' SET ' . $formatter->formatNodesList($this->values);

        if ($this->where !== null) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }
        if ($this->orderBy !== null) {
            $result .= ' ORDER BY ' . $formatter->formatNodesList($this->orderBy);
        }
        if ($this->limit !== null) {
            $result .= ' LIMIT ' . ($this->limit instanceof NodeInterface ? $this->limit->serialize($formatter) : $this->limit);
        }

        return $result;
    }

}
