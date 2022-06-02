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
use SqlFtw\Sql\Expression\QualifiedName;

abstract class InsertOrReplaceCommand implements DmlCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    protected $table;

    /** @var array<string>|null */
    protected $columns;

    /** @var non-empty-array<string>|null */
    protected $partitions;

    /** @var InsertPriority|null */
    protected $priority;

    /** @var bool */
    protected $ignore;

    /**
     * @param array<string>|null $columns
     * @param non-empty-array<string>|null $partitions
     */
    public function __construct(
        QualifiedName $table,
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

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    /**
     * @return array<string>|null
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    /**
     * @return non-empty-array<string>|null
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
                $result .= $formatter->formatNamesList($this->columns);
            }
            $result .= ')';
        }

        return $result;
    }

}
