<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;

/**
 * Column name including table name and schema name, e.g. "foo.bar.baz"
 */
class ColumnName extends Identifier implements ColumnIdentifier
{

    public string $table;

    public string $schema;

    public function __construct(string $name, string $table, string $schema)
    {
        $this->name = $name;
        $this->table = $table;
        $this->schema = $schema;
    }

    public function getTableName(): ?ObjectIdentifier
    {
        return new QualifiedName($this->table, $this->schema);
    }

    public function getFullName(): string
    {
        return $this->schema . '.' . $this->table . '.' . $this->name;
    }

    public function serialize(Formatter $formatter): string
    {
        return $formatter->formatName($this->schema)
            . '.' . $formatter->formatName($this->table)
            . '.' . $formatter->formatName($this->name);
    }

}
