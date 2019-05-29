<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Update;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Dml\OrderByExpression;
use SqlFtw\Sql\Dml\TableReference\TableReferenceList;
use SqlFtw\Sql\Dml\TableReference\TableReferenceNode;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\InvalidDefinitionException;
use function count;

class UpdateCommand implements DmlCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\TableReference\TableReferenceNode */
    private $tableReferences;

    /** @var \SqlFtw\Sql\Dml\Update\SetColumnExpression[] */
    private $values;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode|null */
    private $where;

    /** @var \SqlFtw\Sql\Dml\OrderByExpression[]|null */
    private $orderBy;

    /** @var int|null */
    private $limit;

    /** @var bool */
    private $ignore;

    /** @var bool */
    private $lowPriority;

    /**
     * @param \SqlFtw\Sql\Dml\TableReference\TableReferenceNode $tableReferences
     * @param \SqlFtw\Sql\Dml\Update\SetColumnExpression[] $values
     * @param \SqlFtw\Sql\Expression\ExpressionNode|null $where
     * @param \SqlFtw\Sql\Dml\OrderByExpression[]|null $orderBy
     * @param int|null $limit
     * @param bool $ignore
     * @param bool $lowPriority
     */
    public function __construct(
        TableReferenceNode $tableReferences,
        array $values,
        ?ExpressionNode $where = null,
        ?array $orderBy = null,
        ?int $limit = null,
        bool $ignore = false,
        bool $lowPriority = false
    ) {
        Check::itemsOfType($values, SetColumnExpression::class);
        if ($orderBy !== null) {
            Check::itemsOfType($orderBy, OrderByExpression::class);
        }
        if ($tableReferences instanceof TableReferenceList && count($tableReferences) > 1 && ($orderBy !== null || $limit !== null)) {
            throw new InvalidDefinitionException('ORDER BY and LIMIT must not be set, when more table references are used.');
        }

        $this->tableReferences = $tableReferences;
        $this->values = $values;
        $this->where = $where;
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
     * @return \SqlFtw\Sql\Dml\Update\SetColumnExpression[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function getWhere(): ?ExpressionNode
    {
        return $this->where;
    }

    /**
     * @return \SqlFtw\Sql\Dml\OrderByExpression[]|null
     */
    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    public function getLimit(): ?int
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
        $result = 'UPDATE ';
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
            $result .= ' LIMIT ' . $this->limit;
        }

        return $result;
    }

}
