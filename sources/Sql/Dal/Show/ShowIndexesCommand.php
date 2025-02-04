<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Expression\RootNode;

class ShowIndexesCommand extends ShowCommand
{

    public ObjectIdentifier $table;

    public ?RootNode $where;

    public bool $extended;

    public function __construct(ObjectIdentifier $table, ?RootNode $where = null, bool $extended = false)
    {
        $this->table = $table;
        $this->where = $where;
        $this->extended = $extended;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SHOW ';
        if ($this->extended) {
            $result .= 'EXTENDED ';
        }

        $result .= 'INDEXES FROM ' . $this->table->serialize($formatter);
        if ($this->where !== null) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }

        return $result;
    }

}
