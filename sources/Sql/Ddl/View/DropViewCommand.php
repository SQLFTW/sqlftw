<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\View;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\SchemaObjectsCommand;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Statement;

class DropViewCommand extends Statement implements ViewCommand, SchemaObjectsCommand
{

    /** @var non-empty-array<ObjectIdentifier> */
    private $names;

    /** @var bool */
    private $ifExists;

    /** @var DropViewOption|null */
    private $option;

    /**
     * @param non-empty-array<ObjectIdentifier> $names
     */
    public function __construct(array $names, bool $ifExists = false, ?DropViewOption $option = null)
    {
        $this->names = $names;
        $this->ifExists = $ifExists;
        $this->option = $option;
    }

    /**
     * @return non-empty-array<ObjectIdentifier>
     */
    public function getNames(): array
    {
        return $this->names;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function getOption(): ?DropViewOption
    {
        return $this->option;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP VIEW ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $formatter->formatSerializablesList($this->names);
        if ($this->option !== null) {
            $result .= ' ' . $this->option->serialize($formatter);
        }

        return $result;
    }

}
