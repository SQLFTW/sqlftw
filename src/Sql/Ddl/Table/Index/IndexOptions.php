<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Index;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\TableName;

class IndexOptions implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\SqlSerializable[]|int[]|string[] */
    private $options;

    /**
     * @param \SqlFtw\Sql\SqlSerializable[]|int[]|string[] $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function isEmpty(): bool
    {
        return $this->options === [];
    }

    public function getTable(): ?TableName
    {
        return $this->options[IndexOption::TABLE] ?? null;
    }

    public function getAlgorithm(): ?IndexAlgorithm
    {
        return $this->options[IndexOption::ALGORITHM] ?? null;
    }

    public function getKeyBlockSize(): ?int
    {
        return $this->options[IndexOption::KEY_BLOCK_SIZE] ?? null;
    }

    public function getParser(): ?string
    {
        return $this->options[IndexOption::WITH_PARSER] ?? null;
    }

    public function getVisible(): ?bool
    {
        return $this->options[IndexOption::VISIBLE] ?? null;
    }

    public function getComment(): ?string
    {
        return $this->options[IndexOption::COMMENT] ?? null;
    }

    public function getMergeThreshold(): ?int
    {
        return $this->options[IndexOption::MERGE_THRESHOLD] ?? null;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        /** @var \SqlFtw\Sql\SqlSerializable|int|string $value */
        foreach ($this->options as $option => $value) {
            if ($option === IndexOption::TABLE) {
                $result .= ' ON ' . $value->serialize($formatter);
            } elseif ($option === IndexOption::ALGORITHM) {
                $result .= ' USING ' . $value->serialize($formatter);
            } elseif ($option === IndexOption::KEY_BLOCK_SIZE) {
                $result .= ' KEY_BLOCK_SIZE ' . $value;
            } elseif ($option === IndexOption::WITH_PARSER) {
                $result .= ' WITH PARSER ' . $value->serialize($formatter);
            } elseif ($option === IndexOption::VISIBLE) {
                $result .= ' ' . $value->serialize($formatter);
            } elseif ($option === IndexOption::COMMENT) {
                $result .= ' COMMENT ' . $formatter->formatString($value);
            } elseif ($option === IndexOption::MERGE_THRESHOLD) {
                $result .= " COMMENT 'MERGE_THRESHOLD=" . ((int) $value) . "'";
            }
        }

        return ltrim($result);
    }

}
