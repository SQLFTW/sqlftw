<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\OrderByExpression;

class OrderByAction implements TableAction
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<OrderByExpression> */
    private $columns;

    /**
     * @param non-empty-array<OrderByExpression> $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * @return non-empty-array<OrderByExpression>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ORDER BY ' . $formatter->formatSerializablesList($this->columns);
    }

}
