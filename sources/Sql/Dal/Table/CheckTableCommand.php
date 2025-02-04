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
use SqlFtw\Sql\TableCommand;

class CheckTableCommand extends TableCommand implements DalCommand
{

    /** @var non-empty-list<ObjectIdentifier> */
    public array $names;

    public ?CheckTableOption $option;

    /**
     * @param non-empty-list<ObjectIdentifier> $names
     */
    public function __construct(array $names, ?CheckTableOption $option = null)
    {
        $this->names = $names;
        $this->option = $option;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CHECK TABLE ' . $formatter->formatNodesList($this->names);

        if ($this->option !== null) {
            $result .= ' ' . $this->option->serialize($formatter);
        }

        return $result;
    }

}
