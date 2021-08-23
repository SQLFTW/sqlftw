<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\LogfileGroup;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

class CreateLogfileGroupCommand implements LogfileGroupCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string */
    private $engine;

    /** @var string */
    private $undoFile;

    /** @var int|null */
    private $initialSize;

    /** @var int|null */
    private $undoBufferSize;

    /** @var int|null */
    private $redoBufferSize;

    /** @var int|null */
    private $nodeGroup;

    /** @var bool */
    private $wait;

    /** @var string|null */
    private $comment;

    public function __construct(
        string $name,
        string $engine,
        string $undoFile,
        ?int $initialSize = null,
        ?int $undoBufferSize = null,
        ?int $redoBufferSize = null,
        ?int $nodeGroup = null,
        bool $wait = false,
        ?string $comment = null
    )
    {
        $this->name = $name;
        $this->engine = $engine;
        $this->undoFile = $undoFile;
        $this->initialSize = $initialSize;
        $this->undoBufferSize = $undoBufferSize;
        $this->redoBufferSize = $redoBufferSize;
        $this->nodeGroup = $nodeGroup;
        $this->wait = $wait;
        $this->comment = $comment;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEngine(): string
    {
        return $this->engine;
    }

    public function getUndoFile(): string
    {
        return $this->undoFile;
    }

    public function getInitialSize(): ?int
    {
        return $this->initialSize;
    }

    public function getUndoBufferSize(): ?int
    {
        return $this->undoBufferSize;
    }

    public function getRedoBufferSize(): ?int
    {
        return $this->redoBufferSize;
    }

    public function getNodeGroup(): ?int
    {
        return $this->nodeGroup;
    }

    public function wait(): bool
    {
        return $this->wait;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE LOGFILE GROUP ' . $formatter->formatName($this->name) . ' ADD UNDOFILE ' . $formatter->formatString($this->undoFile);
        if ($this->initialSize !== null) {
            $result .= ' INITIAL_SIZE = ' . $this->initialSize;
        }
        if ($this->undoBufferSize !== null) {
            $result .= ' UNDO_BUFFER_SIZE = ' . $this->undoBufferSize;
        }
        if ($this->redoBufferSize !== null) {
            $result .= ' REDO_BUFFER_SIZE = ' . $this->redoBufferSize;
        }
        if ($this->nodeGroup !== null) {
            $result .= ' NODEGROUP = ' . $this->nodeGroup;
        }
        if ($this->wait) {
            $result .= ' WAIT';
        }
        if ($this->comment !== null) {
            $result .= ' COMMENT = ' . $formatter->formatString($this->comment);
        }
        $result .= ' ENGINE = ' . $formatter->formatName($this->engine);

        return $result;
    }

}
