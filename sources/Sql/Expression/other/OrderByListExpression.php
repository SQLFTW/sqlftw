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
 * ..., ..., ...
 */
class OrderByListExpression implements ArgumentNode
{

    /** @var non-empty-list<OrderByExpression> */
    public array $items;

    /**
     * @param non-empty-list<OrderByExpression> $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function serialize(Formatter $formatter): string
    {
        return $formatter->formatSerializablesList($this->items);
    }

}
