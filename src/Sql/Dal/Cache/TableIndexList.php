<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Cache;

use Dogma\Check;
use Dogma\Type;
use SqlFtw\Sql\Names\TableName;
use SqlFtw\SqlFormatter\SqlFormatter;

class TableIndexList implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\TableName */
    private $table;

    /** @var string[]|null */
    private $indexes;

    /** @var string[]|bool|null */
    private $partitions;

    /** @var bool */
    private $ignoreLeafs;

    /**
     * @param \SqlFtw\Sql\Names\TableName $table
     * @param string[]|null $indexes
     * @param string[]|bool|null $partitions
     * @param bool $ignoreLeafs
     */
    public function __construct(
        TableName $table,
        ?array $indexes = null,
        ?array $partitions = null,
        bool $ignoreLeafs = false
    ) {
        Check::itemsOfType($indexes, Type::STRING, 1);

        $this->table = $table;
        $this->indexes = $indexes;
        $this->partitions = $partitions;
        $this->ignoreLeafs = $ignoreLeafs;
    }

    public function getTable(): TableName
    {
        return $this->table;
    }

    /**
     * @return string[]|null
     */
    public function getIndexes(): ?array
    {
        return $this->indexes;
    }

    /**
     * @return string[]|bool|null
     */
    public function getPartitions()
    {
        return $this->partitions;
    }

    public function ignoreLeafs(): bool
    {
        return $this->ignoreLeafs;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = $this->table->serialize($formatter);

        if ($this->partitions !== null) {
            $result .= ' PARTITION';
            if (is_array($this->partitions)) {
                $result .= ' (' . $formatter->formatNamesList($this->partitions) . ')';
            } else {
                $result .= ' (ALL)';
            }
        }

        if ($this->indexes !== null) {
            $result .= ' INDEX (' . $formatter->formatNamesList($this->indexes) . ')';
        }

        if ($this->ignoreLeafs) {
            $result .= ' IGNORE LEAFS';
        }

        return $result;
    }

}
