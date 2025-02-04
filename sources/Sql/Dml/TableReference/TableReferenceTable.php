<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ObjectIdentifier;

class TableReferenceTable extends TableReferenceNode
{

    public ObjectIdentifier $table;

    public ?string $alias;

    /** @var non-empty-list<string>|null */
    public ?array $partitions;

    /** @var non-empty-list<IndexHint>|null */
    public ?array $indexHints;

    /**
     * @param non-empty-list<string>|null $partitions
     * @param non-empty-list<IndexHint>|null $indexHints
     */
    public function __construct(ObjectIdentifier $table, ?string $alias = null, ?array $partitions = null, ?array $indexHints = null)
    {
        $this->table = $table;
        $this->alias = $alias;
        $this->partitions = $partitions;
        $this->indexHints = $indexHints;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->table->serialize($formatter);
        if ($this->partitions !== null) {
            $result .= ' PARTITION (' . $formatter->formatNamesList($this->partitions) . ')';
        }
        if ($this->alias !== null) {
            $result .= ' AS ' . $formatter->formatName($this->alias);
        }
        if ($this->indexHints !== null) {
            $result .= ' ' . $formatter->formatNodesList($this->indexHints);
        }

        return $result;
    }

}
