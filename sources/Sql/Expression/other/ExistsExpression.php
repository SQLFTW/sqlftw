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
 * EXISTS (SELECT ...)
 */
class ExistsExpression extends RootNode
{

    public Subquery $subquery;

    public function __construct(Subquery $subquery)
    {
        $this->subquery = $subquery;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'EXISTS ' . $this->subquery->serialize($formatter);
    }

}
