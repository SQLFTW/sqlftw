<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Index;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;
use function ltrim;

class IndexOptions implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var int|null */
    private $keyBlockSize;

    /** @var string|null */
    private $withParser;

    /** @var int|null */
    private $mergeThreshold;

    /** @var string|null */
    private $comment;

    /** @var bool|null */
    private $visible;

    public function __construct(
        ?int $keyBlockSize,
        ?string $withParser,
        ?int $mergeThreshold,
        ?string $comment,
        ?bool $visible
    )
    {
        $this->keyBlockSize = $keyBlockSize;
        $this->withParser = $withParser;
        $this->mergeThreshold = $mergeThreshold;
        $this->comment = $comment;
        $this->visible = $visible;
    }

    public function duplicateWithVisibility(?bool $visible): self
    {
        return new self($this->keyBlockSize, $this->withParser, $this->mergeThreshold, $this->comment, $visible);
    }

    public function getKeyBlockSize(): ?int
    {
        return $this->keyBlockSize;
    }

    public function getParser(): ?string
    {
        return $this->withParser;
    }

    public function getVisible(): ?bool
    {
        return $this->visible;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getMergeThreshold(): ?int
    {
        return $this->mergeThreshold;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->keyBlockSize !== null) {
            $result .= ' KEY_BLOCK_SIZE ' . $this->keyBlockSize;
        } elseif ($this->withParser !== null) {
            $result .= ' WITH PARSER ' . $formatter->formatName($this->withParser);
        } elseif ($this->comment !== null) {
            $result .= ' COMMENT ' . $formatter->formatString($this->comment);
        } elseif ($this->mergeThreshold !== null) {
            $result .= " COMMENT 'MERGE_THRESHOLD={$this->mergeThreshold}'";
        } elseif ($this->visible !== null) {
            $result .= ' ' . ($this->visible ? 'VISIBLE' : 'INVISIBLE');
        }

        return ltrim($result);
    }

}
