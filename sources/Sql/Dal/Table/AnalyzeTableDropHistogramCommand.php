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

    /** @var non-empty-list<ObjectIdentifier> */
    private $names;

    /** @var non-empty-array<string> */
    private $columns;

    /** @var bool */
    private $local;

    /**
     * @param non-empty-list<ObjectIdentifier>
     * @param non-empty-array<string> $columns
     */
    public function __construct(array $names, array $columns, bool $local = false)
    {
        $this->names = $names;
        $this->columns = $columns;
        $this->local = $local;
    }

    /**
     * @return non-empty-list<ObjectIdentifier>
     */
    public function getNames(): array
    {
        return $this->names;
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
        $result .= ' TABLE ' . $formatter->formatSerializablesList($this->names)
            . ' DROP HISTOGRAM ON ' . $formatter->formatNamesList($this->columns);

        return $result;
    }

}
