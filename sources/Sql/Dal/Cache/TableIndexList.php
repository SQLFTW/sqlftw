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
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\SqlSerializable;
use function is_array;

class TableIndexList implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $table;

    /** @var string[]|null */
    private $indexes;

    /** @var string[]|bool|null */
    private $partitions;

    /** @var bool */
    private $ignoreLeaves;

    /**
     * @param QualifiedName $table
     * @param string[]|null $indexes
     * @param string[]|true|null $partitions
     * @param bool $ignoreLeaves
     */
    public function __construct(
        QualifiedName $table,
        ?array $indexes = null,
        $partitions = null,
        bool $ignoreLeaves = false
    ) {
        Check::itemsOfType($indexes, Type::STRING, 1);
        if ($partitions !== null && $partitions !== true) {
            Check::itemsOfType($partitions, Type::STRING);
        }

        $this->table = $table;
        $this->indexes = $indexes;
        $this->partitions = $partitions;
        $this->ignoreLeaves = $ignoreLeaves;
    }

    public function getTable(): QualifiedName
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
        return $this->ignoreLeaves;
    }

    public function serialize(Formatter $formatter): string
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

        if ($this->ignoreLeaves) {
            $result .= ' IGNORE LEAVES';
        }

        return $result;
    }

}
