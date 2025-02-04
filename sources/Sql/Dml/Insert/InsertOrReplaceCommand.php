<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Dml\OptimizerHint\OptimizerHint;
use SqlFtw\Sql\Expression\ColumnIdentifier;
use SqlFtw\Sql\Expression\ObjectIdentifier;

abstract class InsertOrReplaceCommand extends Command implements DmlCommand
{

    public ObjectIdentifier $table;

    /** @var list<ColumnIdentifier>|null */
    public ?array $columns;

    /** @var non-empty-list<string>|null */
    public ?array $partitions;

    public ?InsertPriority $priority;

    public bool $ignore;

    /** @var non-empty-list<OptimizerHint>|null */
    public ?array $optimizerHints;

    /**
     * @param list<ColumnIdentifier>|null $columns
     * @param non-empty-list<string>|null $partitions
     * @param non-empty-list<OptimizerHint>|null $optimizerHints
     */
    public function __construct(
        ObjectIdentifier $table,
        ?array $columns = null,
        ?array $partitions = null,
        ?InsertPriority $priority = null,
        bool $ignore = false,
        ?array $optimizerHints = null
    )
    {
        $this->table = $table;
        $this->columns = $columns;
        $this->partitions = $partitions;
        $this->priority = $priority;
        $this->ignore = $ignore;
        $this->optimizerHints = $optimizerHints;
    }

    protected function serializeBody(Formatter $formatter): string
    {
        $result = '';
        if ($this->optimizerHints !== null) {
            $result .= ' /*+ ' . $formatter->formatNodesList($this->optimizerHints) . ' */';
        }

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
                $result .= $formatter->formatNodesList($this->columns);
            }
            $result .= ')';
        }

        return $result;
    }

}
