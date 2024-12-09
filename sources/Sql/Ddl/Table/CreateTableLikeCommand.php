<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\StatementImpl;

class CreateTableLikeCommand extends StatementImpl implements AnyCreateTableCommand
{

    public ObjectIdentifier $name;

    public ObjectIdentifier $templateTable;

    public bool $temporary;

    public bool $ifNotExists;

    public function __construct(
        ObjectIdentifier $name,
        ObjectIdentifier $templateTable,
        bool $temporary = false,
        bool $ifNotExists = false
    ) {
        $this->name = $name;
        $this->templateTable = $templateTable;
        $this->temporary = $temporary;
        $this->ifNotExists = $ifNotExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE ';
        if ($this->temporary) {
            $result .= 'TEMPORARY ';
        }
        $result .= 'TABLE ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS ';
        }
        $result .= $this->name->serialize($formatter);

        $result .= ' LIKE ' . $this->templateTable->serialize($formatter);

        return $result;
    }

}
