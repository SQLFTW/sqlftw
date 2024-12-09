<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Delete;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Dml\OptimizerHint\OptimizerHint;
use SqlFtw\Sql\Dml\TableReference\TableReferenceNode;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Sql\StatementImpl;
use function array_map;
use function implode;

class DeleteCommand extends StatementImpl implements DmlCommand
{

    /** @var non-empty-list<array{ObjectIdentifier, string|null}> */
    public array $tables;

    public ?TableReferenceNode $references;

    /** @var non-empty-list<string>|null */
    public ?array $partitions;

    public ?ExpressionNode $where;

    public ?WithClause $with;

    /** @var non-empty-list<OrderByExpression>|null */
    public ?array $orderBy;

    /** @var int|SimpleName|Placeholder|null */
    public $limit;

    public bool $lowPriority;

    public bool $quick;

    public bool $ignore;

    /** @var non-empty-list<OptimizerHint>|null */
    public ?array $optimizerHints;

    /**
     * @param non-empty-list<array{ObjectIdentifier, string|null}> $tables
     * @param non-empty-list<OrderByExpression>|null $orderBy
     * @param int|SimpleName|Placeholder|null $limit
     * @param non-empty-list<string>|null $partitions
     * @param non-empty-list<OptimizerHint>|null $optimizerHints
     */
    public function __construct(
        array $tables,
        ?ExpressionNode $where = null,
        ?WithClause $with = null,
        ?array $orderBy = null,
        $limit = null,
        ?TableReferenceNode $references = null,
        ?array $partitions = null,
        bool $lowPriority = false,
        bool $quick = false,
        bool $ignore = false,
        ?array $optimizerHints = null
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
        $this->optimizerHints = $optimizerHints;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->with !== null) {
            $result .= $this->with->serialize($formatter) . "\n";
        }

        $result .= 'DELETE ';

        if ($this->optimizerHints !== null) {
            $result .= '/*+ ' . $formatter->formatSerializablesList($this->optimizerHints) . ' */ ';
        }

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
            $result .= ' LIMIT ' . ($this->limit instanceof SqlSerializable ? $this->limit->serialize($formatter) : $this->limit);
        }

        return $result;
    }

}
