<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Transaction;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

class LockTablesCommand implements TransactionCommand
{
    use StrictBehaviorMixin;

    /** @var LockTablesItem[] */
    private $items;

    /**
     * @param LockTablesItem[] $items
     */
    public function __construct(array $items)
    {
        Check::itemsOfType($items, LockTablesItem::class);

        $this->items = $items;
    }

    /**
     * @return LockTablesItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'LOCK TABLES ' . $formatter->formatSerializablesList($this->items);
    }

}
