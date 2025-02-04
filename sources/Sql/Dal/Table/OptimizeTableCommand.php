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
use SqlFtw\Sql\Dal\DalCommand;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\TablesCommand;

class OptimizeTableCommand extends TablesCommand implements DalCommand
{

    /** @var non-empty-list<ObjectIdentifier> */
    public array $names;

    public bool $local;

    /**
     * @param non-empty-list<ObjectIdentifier> $names
     */
    public function __construct(array $names, bool $local = false)
    {
        $this->names = $names;
        $this->local = $local;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'OPTIMIZE';
        if ($this->local) {
            $result .= ' LOCAL';
        }
        $result .= ' TABLE ' . $formatter->formatNodesList($this->names);

        return $result;
    }

}
