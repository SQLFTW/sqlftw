<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\QualifiedName;

abstract class InsertOrReplaceCommand implements DmlCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName */
    protected $table;

    /** @var string[]|null */
    protected $columns;

    /** @var string[]|null */
    protected $partitions;

    /** @var \SqlFtw\Sql\Dml\Insert\InsertPriority|null */
    protected $priority;

    /** @var bool */
    protected $ignore;

    /**
     * @param \SqlFtw\Sql\QualifiedName $table
     * @param string[]|null $columns
     * @param string[]|null $partitions
     * @param \SqlFtw\Sql\Dml\Insert\InsertPriority|null $priority
     * @param bool $ignore
     */
    public function __construct(
        QualifiedName $table,
        ?array $columns,
        ?array $partitions,
        ?InsertPriority $priority = null,
        bool $ignore = false
    )
    {
        $this->table = $table;
        $this->columns = $columns;
        $this->partitions = $partitions;
        $this->priority = $priority;
        $this->ignore = $ignore;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    /**
     * @return string[]|null
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    /**
     * @return string[]|null
     */
    public function getPartitions(): ?array
    {
        return $this->partitions;
    }

    public function getPriority(): ?InsertPriority
    {
        return $this->priority;
    }

    public function getIgnore(): bool
    {
        return $this->ignore;
    }

    protected function serializeBody(Formatter $formatter): string
    {
        $result = '';
        if ($this->priority !== null) {
            $result .= ' ' . $this->priority->serialize($formatter);
        }
        if ($this->ignore) {
            $result .= ' IGNORE';
        }

        $result .= ' INTO ' . $this->table->serialize($formatter);

        if ($this->partitions !== null) {
            $result .= ' PARTITION (' . $formatter->formatNamesList($this->partitions) . ')';
        }
        if ($this->columns !== null) {
            $result .= ' (' . $formatter->formatNamesList($this->columns) . ')';
        }

        return $result;
    }

}
