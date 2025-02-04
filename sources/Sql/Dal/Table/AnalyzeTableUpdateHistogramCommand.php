<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Table;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\TablesCommand;

class AnalyzeTableUpdateHistogramCommand extends TablesCommand
{

    /** @var non-empty-list<string> */
    public array $columns;

    public ?int $buckets;

    public ?string $data;

    public bool $local;

    /**
     * @param non-empty-list<ObjectIdentifier> $tables
     * @param non-empty-list<string> $columns
     */
    public function __construct(array $tables, array $columns, ?int $buckets = null, ?string $data = null, bool $local = false)
    {
        $this->tables = $tables;
        $this->columns = $columns;
        $this->buckets = $buckets;
        $this->data = $data;
        $this->local = $local;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ANALYZE';
        if ($this->local) {
            $result .= ' LOCAL';
        }
        $result .= ' TABLE ' . $formatter->formatNodesList($this->tables)
            . ' UPDATE HISTOGRAM ON ' . $formatter->formatNamesList($this->columns);
        if ($this->buckets !== null) {
            $result .= ' WITH ' . $this->buckets . ' BUCKETS';
        } elseif ($this->data !== null) {
            $result .= ' USING DATA ' . $formatter->formatString($this->data);
        }

        return $result;
    }

}
