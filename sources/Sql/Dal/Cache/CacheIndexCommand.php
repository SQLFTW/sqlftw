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
use SqlFtw\Sql\StatementImpl;
use function is_array;

/**
 * MySQL MyISAM tables only
 */
class CacheIndexCommand extends StatementImpl implements CacheCommand
{

    public string $keyCache;

    /** @var non-empty-list<TableIndexList> */
    private array $tableIndexLists;

    /** @var non-empty-list<string>|bool|null */
    private $partitions;

    /**
     * @param non-empty-list<TableIndexList> $tableIndexLists
     * @param non-empty-list<string>|bool|null $partitions
     */
    public function __construct(string $keyCache, array $tableIndexLists, $partitions = null)
    {
        $this->keyCache = $keyCache;
        $this->tableIndexLists = $tableIndexLists;
        $this->partitions = $partitions;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CACHE INDEX ' . $formatter->formatSerializablesList($this->tableIndexLists);

        if ($this->partitions !== null) {
            $result .= ' PARTITION';
            if (is_array($this->partitions)) {
                $result .= ' (' . $formatter->formatNamesList($this->partitions) . ')';
            } else {
                $result .= ' (ALL)';
            }
        }

        return $result . ' IN ' . $formatter->formatName($this->keyCache);
    }

}
