<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Cache;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use function is_array;

/**
 * MySQL MyISAM tables only
 */
class CacheIndexCommand implements CacheCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $keyCache;

    /** @var \SqlFtw\Sql\Dal\Cache\TableIndexList[] */
    private $tableIndexLists;

    /** @var string[]|bool|null */
    private $partitions;

    /**
     * @param string $keyCache
     * @param \SqlFtw\Sql\Dal\Cache\TableIndexList[] $tableIndexLists
     * @param string[]|bool|null $partitions
     */
    public function __construct(string $keyCache, array $tableIndexLists, $partitions = null)
    {
        Check::itemsOfType($tableIndexLists, TableIndexList::class);
        if (is_array($partitions)) {
            Check::itemsOfType($partitions, Type::STRING, 1);
        }

        $this->keyCache = $keyCache;
        $this->tableIndexLists = $tableIndexLists;
        $this->partitions = $partitions;
    }

    public function getKeyCache(): string
    {
        return $this->keyCache;
    }

    /**
     * @return \SqlFtw\Sql\Dal\Cache\TableIndexList[]
     */
    public function getTableIndexLists(): array
    {
        return $this->tableIndexLists;
    }

    /**
     * @return string[]|bool|null
     */
    public function getPartitions()
    {
        return $this->partitions;
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
