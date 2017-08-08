<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Delete;

use Dogma\Check;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\OrderByExpression;
use SqlFtw\Sql\Dml\TableReference\TableReferenceNode;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\TableName;

class DeleteCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\TableName[] */
    private $tables;

    /** @var \SqlFtw\Sql\Dml\TableReference\TableReferenceNode|null */
    private $references;

    /** @var string[]|null */
    private $partitions;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode|null */
    private $where;

    /** @var \SqlFtw\Sql\Dml\OrderByExpression[]|null */
    private $orderBy;

    /** @var int|null */
    private $limit;

    /** @var bool */
    private $lowPriority;

    /** @var bool */
    private $quick;

    /** @var bool */
    private $ignore;

    /**
     * @param \SqlFtw\Sql\TableName[] $tables
     * @param \SqlFtw\Sql\Expression\ExpressionNode|null $where
     * @param \SqlFtw\Sql\Dml\OrderByExpression[]|null $orderBy
     * @param int|null $limit
     * @param \SqlFtw\Sql\Dml\TableReference\TableReferenceNode|null $references
     * @param string[]|null $partitions
     * @param bool $lowPriority
     * @param bool $quick
     * @param bool $ignore
     */
    public function __construct(
        array $tables,
        ?ExpressionNode $where = null,
        ?array $orderBy = null,
        ?int $limit = null,
        ?TableReferenceNode $references = null,
        ?array $partitions = null,
        bool $lowPriority = false,
        bool $quick = false,
        bool $ignore = false
    ) {
        Check::itemsOfType($tables, TableName::class);
        if ($orderBy !== null) {
            Check::itemsOfType($orderBy, OrderByExpression::class);
        }
        if ($references !== null && $partitions !== null) {
            throw new \SqlFtw\Sql\InvalidDefinitionException('Either table references or partition may be set. Not both a once.');
        } elseif ($references !== null) {
            if ($orderBy !== null || $limit !== null) {
                throw new \SqlFtw\Sql\InvalidDefinitionException('ORDER BY and LIMIT must not be set, when table references are used.');
            }
        } elseif ($partitions !== null) {
            Check::itemsOfType($partitions, Type::STRING);
        }

        $this->tables = $tables;
        $this->where = $where;
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->references = $references;
        $this->partitions = $partitions;
        $this->lowPriority = $lowPriority;
        $this->quick = $quick;
        $this->ignore = $ignore;
    }

    /**
     * @return \SqlFtw\Sql\TableName[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function getReferences(): ?TableReferenceNode
    {
        return $this->references;
    }

    /**
     * @return string[]|null
     */
    public function getPartitions(): ?array
    {
        return $this->partitions;
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

    public function lowPriority(): bool
    {
        return $this->lowPriority;
    }

    public function quick(): bool
    {
        return $this->quick;
    }

    public function ignore(): bool
    {
        return $this->ignore;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DELETE ';
        if ($this->lowPriority) {
            $result .= 'LOW_PRIORITY ';
        }
        if ($this->quick) {
            $result .= 'QUICK ';
        }
        if ($this->ignore) {
            $result .= 'IGNORE ';
        }

        $result .= 'FROM ' . $formatter->formatSerializablesList($this->tables);
        if ($this->references !== null) {
            $result .= ' USING ' . $this->references->serialize($formatter);
        } elseif ($this->partitions !== null) {
            $result .= ' PARTITION (' . $formatter->formatNamesList($this->partitions) . ')';
        }

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
