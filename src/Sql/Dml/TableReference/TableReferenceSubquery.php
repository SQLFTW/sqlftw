<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

use Dogma\Check;
use Dogma\Type;
use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\SqlFormatter\SqlFormatter;

class TableReferenceSubquery implements \SqlFtw\Sql\Dml\TableReference\TableReferenceNode, \Countable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\Select\SelectCommand */
    private $query;

    /** @var string|null */
    private $alias;

    /** @var string[]|null */
    private $columnList;

    /**
     * @param \SqlFtw\Sql\Dml\Select\SelectCommand $query
     * @param string|null $alias
     * @param array|null $columnList
     */
    public function __construct(SelectCommand $query, ?string $alias, ?array $columnList)
    {
        if ($columnList !== null) {
            Check::itemsOfType($columnList, Type::STRING);
        }
        $this->query = $query;
        $this->alias = $alias;
        $this->columnList = $columnList;
    }

    public function getType(): TableReferenceNodeType
    {
        return TableReferenceNodeType::get(TableReferenceNodeType::PARENTHESES);
    }

    public function count(): int
    {
        return $this->query instanceof \Countable ? $this->query->count() : 1;
    }

    public function getQuery(): SelectCommand
    {
        return $this->query;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getColumnList(): ?array
    {
        return $this->columnList;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = $this->query->serialize($formatter);
        if ($this->alias !== null) {
            $result .= ' AS ' . $formatter->formatName($this->alias);
        }
        if ($this->columnList !== null) {
            $result .= ' (' . $formatter->formatNamesList($this->columnList) . ')';
        }

        return $result;
    }

}
