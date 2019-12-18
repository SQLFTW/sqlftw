<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\Sql\SqlSerializable;

class WithExpression implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\Select\SelectCommand */
    private $query;

    /** @var string */
    private $name;

    /** @var string[]|null */
    private $columns;

    /**
     * @param \SqlFtw\Sql\Dml\Select\SelectCommand $query
     * @param string $name
     * @param string[]|null $columns
     */
    public function __construct(SelectCommand $query, string $name, ?array $columns = null)
    {
        $this->query = $query;
        $this->name = $name;
        $this->columns = $columns;
    }

    public function getQuery(): SelectCommand
    {
        return $this->query;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]|null
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->name;
        if ($this->columns !== null) {
            $result .= ' (' . $formatter->formatNamesList($this->columns) . ')';
        }

        return $result . ' AS (' . $this->query->serialize($formatter) . ')';
    }

}
