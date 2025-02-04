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

class ChecksumTableCommand extends TablesCommand
{

    public bool $quick;

    public bool $extended;

    /**
     * @param non-empty-list<ObjectIdentifier> $tables
     */
    public function __construct(array $tables, bool $quick, bool $extended)
    {
        $this->tables = $tables;
        $this->quick = $quick;
        $this->extended = $extended;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CHECKSUM TABLE ' . $formatter->formatNodesList($this->tables);

        if ($this->quick) {
            $result .= ' QUICK';
        }
        if ($this->extended) {
            $result .= ' EXTENDED';
        }

        return $result;
    }

}
