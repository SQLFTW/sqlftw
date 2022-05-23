<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

use Countable;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\Query\Query;

class TableReferenceSubquery implements TableReferenceNode, Countable
{
    use StrictBehaviorMixin;

    /** @var Query */
    private $query;

    /** @var string|null */
    private $alias;

    /** @var non-empty-array<string>|null */
    private $columnList;

    /** @var bool */
    private $parentheses;

    /** @var bool */
    private $lateral;

    /**
     * @param non-empty-array<string>|null $columnList
     */
    public function __construct(
        Query $query,
        ?string $alias,
        ?array $columnList,
        bool $parentheses = false,
        bool $lateral = false
    ) {
        $this->query = $query;
        $this->alias = $alias;
        $this->columnList = $columnList;
        $this->parentheses = $parentheses;
        $this->lateral = $lateral;
    }

    public function count(): int
    {
        return $this->query instanceof Countable ? $this->query->count() : 1;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @return non-empty-array<string>|null
     */
    public function getColumnList(): ?array
    {
        return $this->columnList;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->lateral) {
            $result .= 'LATERAL ';
        }

        if ($this->parentheses) {
            $result .= '(';
        }
        $result .= $this->query->serialize($formatter);
        if ($this->parentheses) {
            $result .= ')';
        }

        if ($this->alias !== null) {
            $result .= ' AS ' . $formatter->formatName($this->alias);
        }
        if ($this->columnList !== null) {
            $result .= ' (' . $formatter->formatNamesList($this->columnList) . ')';
        }

        return $result;
    }

}
