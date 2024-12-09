<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Schema;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\StatementImpl;

class DropSchemaCommand extends StatementImpl implements SchemaCommand
{

    public string $schema;

    public bool $ifExists;

    public function __construct(string $schema, bool $ifExists = false)
    {
        $this->schema = $schema;
        $this->ifExists = $ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP SCHEMA ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $formatter->formatName($this->schema);

        return $result;
    }

}
