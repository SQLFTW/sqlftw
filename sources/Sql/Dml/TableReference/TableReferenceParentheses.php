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

class TableReferenceParentheses implements TableReferenceNode, Countable
{
    use StrictBehaviorMixin;

    /** @var TableReferenceNode */
    private $content;

    /** @var string|null */
    private $alias;

    /** @var non-empty-array<string>|null */
    private $columnList;

    /**
     * @param non-empty-array<string>|null $columnList
     */
    public function __construct(TableReferenceNode $content, ?string $alias = null, ?array $columnList = null)
    {
        $this->content = $content;
        $this->alias = $alias;
        $this->columnList = $columnList;
    }

    public function count(): int
    {
        return $this->content instanceof Countable ? $this->content->count() : 1;
    }

    public function getContent(): TableReferenceNode
    {
        return $this->content;
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
        $result = $this->content->serialize($formatter);

        if ($this->alias !== null) {
            $result .= ' AS ' . $formatter->formatName($this->alias);
        }
        if ($this->columnList !== null) {
            $result .= ' (' . $formatter->formatNamesList($this->columnList) . ')';
        }

        return $result;
    }

}
