<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\Query\Query;

class Subquery implements ExpressionNode
{
    use StrictBehaviorMixin;

    /** @var Query */
    private $subquery;

    public function __construct(Query $subquery)
    {
        $this->subquery = $subquery;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::SUBQUERY);
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
