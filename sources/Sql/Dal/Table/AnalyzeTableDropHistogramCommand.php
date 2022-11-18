<?php declare(strict_types = 1);
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
use SqlFtw\Sql\Statement;

class AnalyzeTableDropHistogramCommand extends Statement implements DalTablesCommand
{

    /** @var non-empty-array<ObjectIdentifier> */
    private $tables;

    /** @var non-empty-array<string> */
    private $columns;

    /** @var bool */
    private $local;

    /**
     * @param non-empty-array<ObjectIdentifier> $tables
     * @param non-empty-array<string> $columns
     */
    public function __construct(array $tables, array $columns, bool $local = false)
    {
        $this->tables = $tables;
        $this->columns = $columns;
        $this->local = $local;
    }

    /**
     * @return non-empty-array<ObjectIdentifier>
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * @return non-empty-array<string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ANALYZE';
        if ($this->local) {
            $result .= ' LOCAL';
        }
        $result .= ' TABLE ' . $formatter->formatSerializablesList($this->tables)
            . ' DROP HISTOGRAM ON ' . $formatter->formatNamesList($this->columns);

        return $result;
    }

}
