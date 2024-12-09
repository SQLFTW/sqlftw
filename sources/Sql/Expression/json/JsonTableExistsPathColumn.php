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

class JsonTableExistsPathColumn implements JsonTableColumn
{

    public string $name;

    public ColumnType $type;

    public StringValue $path;

    public function __construct(string $name, ColumnType $type, StringValue $path)
    {
        $this->name = $name;
        $this->type = $type;
        $this->path = $path;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->name . ' ' . $this->type->serialize($formatter) . ' EXISTS PATH ' . $this->path->serialize($formatter);
    }

}
