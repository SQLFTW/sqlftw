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

class OrderByAction implements TableAction
{
    use StrictBehaviorMixin;

    /** @var string[] */
    private $columns;

    /**
     * @param string[] $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ORDER BY ' . $formatter->formatNamesList($this->columns);
    }

}
