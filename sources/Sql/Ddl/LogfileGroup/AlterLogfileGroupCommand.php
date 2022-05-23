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
use SqlFtw\Sql\Expression\SizeLiteral;

class AlterLogfileGroupCommand implements LogfileGroupCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string */
    private $engine;

    /** @var string */
    private $undoFile;

    /** @var SizeLiteral|null */
    private $initialSize;

    /** @var bool */
    private $wait;

    public function __construct(string $name, string $engine, string $undoFile, ?SizeLiteral $initialSize = null, bool $wait = false)
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

    public function getEngine(): string
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
        $result .= ' ENGINE = ' . $formatter->formatName($this->engine);

        return $result;
    }

}
