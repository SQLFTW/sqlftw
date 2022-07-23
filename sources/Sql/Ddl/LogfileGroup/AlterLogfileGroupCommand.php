<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\LogfileGroup;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Option\StorageEngine;
use SqlFtw\Sql\Expression\SizeLiteral;
use SqlFtw\Sql\Statement;

class AlterLogfileGroupCommand extends Statement implements LogfileGroupCommand
{

    /** @var string */
    private $name;

    /** @var StorageEngine|null */
    private $engine;

    /** @var string */
    private $undoFile;

    /** @var SizeLiteral|null */
    private $initialSize;

    /** @var bool */
    private $wait;

    public function __construct(string $name, ?StorageEngine $engine, string $undoFile, ?SizeLiteral $initialSize = null, bool $wait = false)
    {
        $this->name = $name;
        $this->engine = $engine;
        $this->undoFile = $undoFile;
        $this->initialSize = $initialSize;
        $this->wait = $wait;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEngine(): ?StorageEngine
    {
        return $this->engine;
    }

    public function getUndoFile(): string
    {
        return $this->undoFile;
    }

    public function getInitialSize(): ?SizeLiteral
    {
        return $this->initialSize;
    }

    public function wait(): bool
    {
        return $this->wait;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER LOGFILE GROUP ' . $formatter->formatName($this->name) . ' ADD UNDOFILE ' . $formatter->formatString($this->undoFile);
        if ($this->initialSize !== null) {
            $result .= ' INITIAL_SIZE = ' . $this->initialSize->serialize($formatter);
        }
        if ($this->wait) {
            $result .= ' WAIT';
        }
        if ($this->engine !== null) {
            $result .= ' ENGINE = ' . $this->engine->serialize($formatter);
        }

        return $result;
    }

}
