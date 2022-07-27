<?php declare(strict_types = 1);
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

class TableReferenceTable implements TableReferenceNode
{

    /** @var ObjectIdentifier */
    private $table;

    /** @var string|null */
    private $alias;

    /** @var non-empty-array<string>|null */
    private $partitions;

    /** @var non-empty-array<IndexHint>|null */
    private $indexHints;

    /**
     * @param non-empty-array<string>|null $partitions
     * @param non-empty-array<IndexHint>|null $indexHints
     */
    public function __construct(ObjectIdentifier $table, ?string $alias = null, ?array $partitions = null, ?array $indexHints = null)
    {
        $this->table = $table;
        $this->alias = $alias;
        $this->partitions = $partitions;
        $this->indexHints = $indexHints;
    }

    public function getTable(): ObjectIdentifier
    {
        return $this->table;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @return non-empty-array<string>|null
     */
    public function getPartitions(): ?array
    {
        return $this->partitions;
    }

    /**
     * @return non-empty-array<IndexHint>|null
     */
    public function getIndexHints(): ?array
    {
        return $this->indexHints;
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
            $result .= ' ' . $formatter->formatSerializablesList($this->indexHints);
        }

        return $result;
    }

}
