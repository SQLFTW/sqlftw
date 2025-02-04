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

class JsonTableColumnsList extends ArgumentValue
{

    /** @var non-empty-list<JsonTableColumn> */
    public array $columns;

    /**
     * @param non-empty-list<JsonTableColumn> $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    public function serialize(Formatter $formatter): string
    {
        return '(' . $formatter->formatNodesList($this->columns) . ')';
    }

}
