<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Update;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\Assignment;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Dml\TableReference\TableReferenceList;
use SqlFtw\Sql\Dml\TableReference\TableReferenceNode;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Statement;
use function count;

class UpdateCommand extends Statement implements DmlCommand
{
    use StrictBehaviorMixin;

    /** @var TableReferenceNode */
    private $tableReferences;

    /** @var non-empty-array<Assignment> */
    private $values;

    /** @var RootNode|null */
    private $where;

    /** @var WithClause|null */
    private $with;

    /** @var non-empty-array<OrderByExpression>|null */
    private $orderBy;

    /** @var int|SimpleName|null */
    private $limit;

    /** @var bool */
    private $ignore;

    /** @var bool */
    private $lowPriority;

    /**
     * @param non-empty-array<Assignment> $values
     * @param non-empty-array<OrderByExpression>|null $orderBy
     * @param int|SimpleName|null $limit
     */
    public function __construct(
        TableReferenceNode $tableReferences,
        array $values,
        ?RootNode $where = null,
        ?WithClause $with = null,
        ?array $orderBy = null,
        $limit = null,
        bool $ignore = false,
        bool $lowPriority = false
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
    }

    public function getTableReferences(): TableReferenceNode
    {
        return $this->tableReferences;
    }

    /**
     * @return non-empty-array<Assignment>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function getWhere(): ?RootNode
    {
        return $this->where;
    }

    public function getWith(): ?WithClause
    {
        return $this->with;
    }

    /**
     * @return non-empty-array<OrderByExpression>|null
     */
    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    /**
     * @return int|SimpleName|null
     */
    public function getLimit()
    {
        return $this->limit;
    }

    public function ignore(): bool
    {
        return $this->ignore;
    }

    public function lowPriority(): bool
    {
        return $this->lowPriority;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->with !== null) {
            $result .= $this->with->serialize($formatter) . "\n";
        }

        $result .= 'UPDATE ';
        if ($this->lowPriority) {
            $result .= 'LOW_PRIORITY ';
        }
        if ($this->ignore) {
            $result .= 'IGNORE ';
        }

        $result .= $this->tableReferences->serialize($formatter);
        $result .= ' SET ' . $formatter->formatSerializablesList($this->values);

        if ($this->where !== null) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }
        if ($this->orderBy !== null) {
            $result .= ' ORDER BY ' . $formatter->formatSerializablesList($this->orderBy);
        }
        if ($this->limit !== null) {
            $result .= ' LIMIT ' . ($this->limit instanceof SimpleName ? $this->limit->serialize($formatter) : $this->limit);
        }

        return $result;
    }

}
