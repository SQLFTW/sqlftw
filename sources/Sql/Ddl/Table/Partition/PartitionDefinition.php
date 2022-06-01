<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Partition;

use Dogma\Arr;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\SqlSerializable;
use function implode;
use function is_array;
use function is_int;

class PartitionDefinition implements SqlSerializable
{
    use StrictBehaviorMixin;

    public const MAX_VALUE = true;

    /** @var string */
    private $name;

    /** @var non-empty-array<string|int|float|bool|Literal>|RootNode|bool|null */
    private $lessThan;

    /** @var non-empty-array<RootNode>|null */
    private $values;

    /** @var non-empty-array<string, int|string>|null */
    private $options;

    /** @var non-empty-array<string, non-empty-array<int|string>|null>|null */
    private $subpartitions;

    /**
     * @param non-empty-array<string|int|float|bool|Literal>|RootNode|bool|null $lessThan
     * @param non-empty-array<RootNode>|null $values
     * @param non-empty-array<string, int|string>|null $options
     * @param non-empty-array<string, non-empty-array<int|string>|null>|null $subpartitions
     */
    public function __construct(string $name, $lessThan, ?array $values = null, ?array $options = null, ?array $subpartitions = null)
    {
        if ($options !== null) {
            foreach ($options as $option => $value) {
                PartitionOption::get($option);
            }
        }
        if ($subpartitions !== null) {
            foreach ($subpartitions as $subpartitionOptions) {
                if ($subpartitionOptions !== null) {
                    foreach ($subpartitionOptions as $option => $value) {
                        PartitionOption::get($option);
                    }
                }
            }
        }

        $this->name = $name;
        $this->lessThan = $lessThan;
        $this->values = $values;
        $this->options = $options;
        $this->subpartitions = $subpartitions;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return non-empty-array<string|int|float|bool|Literal>|RootNode|bool|null
     */
    public function getLessThan()
    {
        return $this->lessThan;
    }

    /**
     * @return non-empty-array<RootNode>|null
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    /**
     * @return non-empty-array<string, int|string>|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * @return non-empty-array<string, non-empty-array<int|string>|null>|null
     */
    public function getSubpartitions(): ?array
    {
        return $this->subpartitions;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'PARTITION ' . $formatter->formatName($this->name);

        if ($this->lessThan !== null) {
            $result .= ' VALUES LESS THAN ';
            if ($this->lessThan instanceof RootNode) {
                $result .= '(' . $this->lessThan->serialize($formatter) . ')';
            } elseif (is_array($this->lessThan)) {
                $result .= '(' . $formatter->formatValuesList($this->lessThan) . ')';
            } else {
                $result .= 'MAXVALUE';
            }
        } elseif ($this->values !== null) {
            $result .= ' VALUES IN (' . $formatter->formatSerializablesList($this->values) . ')';
        }
        if ($this->options !== null) {
            foreach ($this->options as $option => $value) {
                $result .= ' ' . $option . ' = ' . (is_int($value) ? $value : $formatter->formatString($value));
            }
        }
        if ($this->subpartitions !== null) {
            $result .= ' (' . implode(', ', Arr::mapPairs($this->subpartitions, static function ($name, $options) use ($formatter): string {
                $sub = 'SUBPARTITION ' . $formatter->formatName($name);
                if ($options !== null) {
                    foreach ($options as $option => $value) {
                        $sub .= ' ' . $option . ' = ' . (is_int($value) ? $value : $formatter->formatString($value));
                    }
                }

                return $sub;
            })) . ')';
        }

        return $result;
    }

}
