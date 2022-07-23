<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Cache;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

/**
 * MySQL MyISAM tables only
 */
class LoadIndexIntoCacheCommand extends Statement implements CacheCommand
{

    /** @var non-empty-array<TableIndexList> */
    private $tableIndexLists;

    /**
     * @param non-empty-array<TableIndexList> $tableIndexLists
     */
    public function __construct(array $tableIndexLists)
    {
        $this->tableIndexLists = $tableIndexLists;
    }

    /**
     * @return non-empty-array<TableIndexList>
     */
    public function getTableIndexLists(): array
    {
        return $this->tableIndexLists;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'LOAD INDEX INTO CACHE ' . $formatter->formatSerializablesList($this->tableIndexLists);
    }

}
