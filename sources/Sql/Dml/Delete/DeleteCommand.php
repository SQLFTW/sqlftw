<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Delete;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Dml\TableReference\TableReferenceNode;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\InvalidDefinitionException;
use function array_map;
use function implode;

class DeleteCommand implements DmlCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<array{QualifiedName, string|null}> */
    private $tables;

    /** @var TableReferenceNode|null */
    private $references;

    /** @var non-empty-array<string>|null */
    private $partitions;

    /** @var ExpressionNode|null */
    private $where;

    /** @var WithClause|null */
    private $with;

    /** @var non-empty-array<OrderByExpression>|null */
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
     * @param non-empty-array<array{QualifiedName, string|null}> $tables
     * @param non-empty-array<OrderByExpression>|null $orderBy
     * @param non-empty-array<string>|null $partitions
     */
    public function __construct(
        array $tables,
        ?ExpressionNode $where = null,
        ?WithClause $with = null,
        ?array $orderBy = null,
        ?int $limit = null,
        ?TableReferenceNode $references = null,
        ?array $partitions = null,
        bool $lowPriority = false,
        bool $quick = false,
        bool $ignore = false
    ) {
        if ($references !== null && $partitions !== null) {
            throw new InvalidDefinitionException('Either table references or partition may be set. Not both a once.');
        } elseif ($references !== null) {
            if ($orderBy !== null || $limit !== null) {
                throw new InvalidDefinitionException('ORDER BY and LIMIT must not be set, when table references are used.');
            }
        }

        $this->tables = $tables;
        $this->where = $where;
        $this->with = $with;
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->references = $references;
        $this->partitions = $partitions;
        $this->lowPriority = $lowPriority;
        $this->quick = $quick;
        $this->ignore = $ignore;
    }

    /**
     * @return non-empty-array<array{QualifiedName, string|null}>
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
     * @return non-empty-array<string>|null
     */
    public function getPartitions(): ?array
    {
        return $this->partitions;
    }

    public function getWhere(): ?ExpressionNode
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
        $result = '';
        if ($this->with !== null) {
            $result .= $this->with->serialize($formatter) . "\n";
        }

        $result .= 'DELETE ';
        if ($this->lowPriority) {
            $result .= 'LOW_PRIORITY ';
        }
        if ($this->quick) {
            $result .= 'QUICK ';
        }
        if ($this->ignore) {
            $result .= 'IGNORE ';
        }

        $result .= 'FROM ' . implode(', ', array_map(static function (array $table) use ($formatter): string {
            return $table[0]->serialize($formatter) . ($table[1] !== null ? ' AS ' . $table[1] : '');
        }, $this->tables));
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
