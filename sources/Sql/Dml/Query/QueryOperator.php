<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Node;

class QueryOperator extends Node
{

    public QueryOperatorType $type;

    public QueryOperatorOption $option;

    public function __construct(QueryOperatorType $type, QueryOperatorOption $option)
    {
        $this->type = $type;
        $this->option = $option;
    }

    public function serialize(Formatter $formatter): string
    {
        $option = $this->option->serialize($formatter);

        return $this->type->serialize($formatter) . ($option !== '' ? ' ' . $option : '');
    }

}
