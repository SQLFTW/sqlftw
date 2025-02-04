<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Flush;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dal\DalCommand;
use SqlFtw\Sql\Expression\ObjectIdentifier;

class FlushTablesCommand extends Command implements DalCommand
{

    /** @var non-empty-list<ObjectIdentifier>|null */
    public ?array $tables;

    public bool $withReadLock;

    public bool $forExport;

    public bool $local;

    /**
     * @param non-empty-list<ObjectIdentifier>|null $tables
     */
    public function __construct(
        ?array $tables = null,
        bool $withReadLock = false,
        bool $forExport = false,
        bool $local = false
    ) {
        $this->tables = $tables;
        $this->withReadLock = $withReadLock;
        $this->forExport = $forExport;
        $this->local = $local;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'FLUSH ';
        if ($this->local) {
            $result .= 'LOCAL ';
        }
        $result .= 'TABLES';

        if ($this->tables !== null) {
            $result .= ' ' . $formatter->formatNodesList($this->tables);
        }
        if ($this->withReadLock) {
            $result .= ' WITH READ LOCK';
        }
        if ($this->forExport) {
            $result .= ' FOR EXPORT';
        }

        return $result;
    }

}
