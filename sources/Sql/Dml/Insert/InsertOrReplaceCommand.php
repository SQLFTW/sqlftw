<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Expression\ColumnIdentifier;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Statement;

abstract class InsertOrReplaceCommand extends Statement implements DmlCommand
{

    protected ObjectIdentifier $table;

    /** @var list<ColumnIdentifier>|null */
    protected ?array $columns;

    /** @var non-empty-list<string>|null */
    protected ?array $partitions;

    protected ?InsertPriority $priority;

    protected bool $ignore;

    /**
     * @param list<ColumnIdentifier>|null $columns
     * @param non-empty-list<string>|null $partitions
     */
    public function __construct(
        ObjectIdentifier $table,
        ?array $columns = null,
        ?array $partitions = null,
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

    public function getTable(): ObjectIdentifier
    {
        return $this->table;
    }

    /**
     * @return list<ColumnIdentifier>|null
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    /**
     * @return non-empty-list<string>|null
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
            $result .= ' (';
            if ($this->columns !== []) {
                $result .= $formatter->formatSerializablesList($this->columns);
            }
            $result .= ')';
        }

        return $result;
    }

}
