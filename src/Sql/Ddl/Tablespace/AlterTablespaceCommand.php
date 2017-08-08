<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Tablespace;

use SqlFtw\Formatter\Formatter;

class AlterTablespaceCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string */
    private $file;

    /** @var bool */
    private $drop;

    /** @var bool */
    private $wait;

    /** @var int|null */
    private $initialSize;

    /** @var string|null */
    private $engine;

    public function __construct(string $name, string $file, bool $drop, bool $wait, ?int $initialSize = null, ?string $engine = null)
    {
        $this->name = $name;
        $this->file = $file;
        $this->drop = $drop;
        $this->wait = $wait;
        $this->initialSize = $initialSize;
        $this->engine = $engine;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function drop(): bool
    {
        return $this->drop;
    }

    public function wait(): bool
    {
        return $this->wait;
    }

    public function getInitialSize(): ?int
    {
        return $this->initialSize;
    }

    public function getEngine(): ?string
    {
        return $this->engine;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER TABLESPACE ' . $formatter->formatName($this->name);
        if ($this->drop) {
            $result .= ' DROP DATAFILE ' . $formatter->formatString($this->file);
        } else {
            $result .= ' ADD DATAFILE ' . $formatter->formatString($this->file);
        }
        if ($this->initialSize !== null) {
            $result .= ' INITIAL_SIZE = ' . $this->initialSize;
        }
        if ($this->wait) {
            $result .= ' WAIT';
        }
        if ($this->engine !== null) {
            $result .= ' ENGINE = ' . $formatter->formatName($this->engine);
        }

        return $result;
    }

}
