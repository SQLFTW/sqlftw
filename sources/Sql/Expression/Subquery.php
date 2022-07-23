<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\Query\Query;

/**
 * (SELECT ...)
 */
class Subquery implements ExpressionNode
{

    /** @var Query */
    private $subquery;

    public function __construct(Query $subquery)
    {
        $this->subquery = $subquery;
    }

    public function getSubquery(): Query
    {
        return $this->subquery;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->subquery->serialize($formatter);
    }

}
