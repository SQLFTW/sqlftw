<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Transaction;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;

class LockTablesCommand extends Command implements TransactionCommand
{

    /** @var non-empty-list<LockTablesItem> */
    public array $items;

    /**
     * @param non-empty-list<LockTablesItem> $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'LOCK TABLES ' . $formatter->formatNodesList($this->items);
    }

}
