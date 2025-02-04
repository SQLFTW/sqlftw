<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\OrderByExpression;

class OrderByAction extends TableAction
{

    /** @var non-empty-list<OrderByExpression> */
    public array $columns;

    /**
     * @param non-empty-list<OrderByExpression> $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ORDER BY ' . $formatter->formatNodesList($this->columns);
    }

}
