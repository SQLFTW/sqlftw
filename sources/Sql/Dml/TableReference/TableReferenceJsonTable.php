<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\FunctionCall;

class TableReferenceJsonTable extends TableReferenceNode
{

    public FunctionCall $table;

    public ?string $alias;

    public function __construct(FunctionCall $table, ?string $alias)
    {
        $this->table = $table;
        $this->alias = $alias;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->table->serialize($formatter);

        if ($this->alias !== null) {
            $result .= ' AS ' . $formatter->formatName($this->alias);
        }

        return $result;
    }

}
