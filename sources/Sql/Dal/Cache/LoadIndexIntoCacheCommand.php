<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Cache;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;

/**
 * MySQL MyISAM tables only
 */
class LoadIndexIntoCacheCommand extends Command implements CacheCommand
{

    /** @var non-empty-list<TableIndexList> */
    public array $tableIndexLists;

    /**
     * @param non-empty-list<TableIndexList> $tableIndexLists
     */
    public function __construct(array $tableIndexLists)
    {
        $this->tableIndexLists = $tableIndexLists;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'LOAD INDEX INTO CACHE ' . $formatter->formatNodesList($this->tableIndexLists);
    }

}
