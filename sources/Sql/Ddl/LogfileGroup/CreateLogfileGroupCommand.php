<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\LogfileGroup;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Table\Option\StorageEngine;
use SqlFtw\Sql\Expression\SizeLiteral;

class CreateLogfileGroupCommand extends Command implements LogfileGroupCommand
{

    public string $logFileGroup;

    public ?StorageEngine $engine;

    public string $undoFile;

    public ?SizeLiteral $initialSize;

    public ?SizeLiteral $undoBufferSize;

    public ?SizeLiteral $redoBufferSize;

    public ?int $nodeGroup;

    public bool $wait;

    public ?string $comment;

    public function __construct(
        string $logFileGroup,
        ?StorageEngine $engine,
        string $undoFile,
        ?SizeLiteral $initialSize = null,
        ?SizeLiteral $undoBufferSize = null,
        ?SizeLiteral $redoBufferSize = null,
        ?int $nodeGroup = null,
        bool $wait = false,
        ?string $comment = null
    )
    {
        $this->logFileGroup = $logFileGroup;
        $this->engine = $engine;
        $this->undoFile = $undoFile;
        $this->initialSize = $initialSize;
        $this->undoBufferSize = $undoBufferSize;
        $this->redoBufferSize = $redoBufferSize;
        $this->nodeGroup = $nodeGroup;
        $this->wait = $wait;
        $this->comment = $comment;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE LOGFILE GROUP ' . $formatter->formatName($this->logFileGroup) . ' ADD UNDOFILE ' . $formatter->formatString($this->undoFile);
        if ($this->initialSize !== null) {
            $result .= ' INITIAL_SIZE ' . $this->initialSize->serialize($formatter);
        }
        if ($this->undoBufferSize !== null) {
            $result .= ' UNDO_BUFFER_SIZE ' . $this->undoBufferSize->serialize($formatter);
        }
        if ($this->redoBufferSize !== null) {
            $result .= ' REDO_BUFFER_SIZE ' . $this->redoBufferSize->serialize($formatter);
        }
        if ($this->nodeGroup !== null) {
            $result .= ' NODEGROUP ' . $this->nodeGroup;
        }
        if ($this->wait) {
            $result .= ' WAIT';
        }
        if ($this->comment !== null) {
            $result .= ' COMMENT ' . $formatter->formatString($this->comment);
        }
        if ($this->engine !== null) {
            $result .= ' ENGINE ' . $this->engine->serialize($formatter);
        }

        return $result;
    }

}
