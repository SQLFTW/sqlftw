<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\View;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\SchemaObjectsCommand;
use SqlFtw\Sql\Expression\ObjectIdentifier;

class DropViewCommand extends Command implements ViewCommand, SchemaObjectsCommand
{

    /** @var non-empty-list<ObjectIdentifier> */
    public array $views;

    public bool $ifExists;

    public ?DropViewOption $option;

    /**
     * @param non-empty-list<ObjectIdentifier> $views
     */
    public function __construct(array $views, bool $ifExists = false, ?DropViewOption $option = null)
    {
        $this->views = $views;
        $this->ifExists = $ifExists;
        $this->option = $option;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP VIEW ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $formatter->formatNodesList($this->views);
        if ($this->option !== null) {
            $result .= ' ' . $this->option->serialize($formatter);
        }

        return $result;
    }

}
