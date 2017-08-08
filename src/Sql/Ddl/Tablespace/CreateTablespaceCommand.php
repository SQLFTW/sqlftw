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

class CreateTablespaceCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string */
    private $file;

    /** @var int|null */
    private $fileBlockSize;

    /** @var string|null */
    private $engine;

    public function __construct(string $name, string $file, ?int $fileBlockSize = null, ?string $engine = null)
    {
        $this->name = $name;
        $this->file = $file;
        $this->fileBlockSize = $fileBlockSize;
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

    public function getFileBlockSize(): ?int
    {
        return $this->fileBlockSize;
    }

    public function getEngine(): ?string
    {
        return $this->engine;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE TABLESPACE ' . $formatter->formatName($this->name);
        $result .= ' ADD DATAFILE ' . $formatter->formatString($this->file);
        if ($this->fileBlockSize !== null) {
            $result .= ' FILE_BLOCK_SIZE = ' . $this->fileBlockSize;
        }
        if ($this->engine !== null) {
            $result .= ' ENGINE = ' . $formatter->formatName($this->engine);
        }

        return $result;
    }

}
