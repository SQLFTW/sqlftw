<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Expression\RootNode;

class ShowColumnsCommand extends ShowCommand
{

    public ObjectIdentifier $table;

    public ?string $like;

    public ?RootNode $where;

    public bool $full;

    public bool $extended;

    public function __construct(
        ObjectIdentifier $table,
        ?string $like = null,
        ?RootNode $where = null,
        bool $full = false,
        bool $extended = false
    )
    {
        $this->table = $table;
        $this->like = $like;
        $this->where = $where;
        $this->full = $full;
        $this->extended = $extended;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SHOW';
        if ($this->extended) {
            $result .= ' EXTENDED';
        }
        if ($this->full) {
            $result .= ' FULL';
        }
        $result .= ' COLUMNS FROM ' . $this->table->serialize($formatter);
        if ($this->like !== null) {
            $result .= ' LIKE ' . $formatter->formatString($this->like);
        } elseif ($this->where !== null) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }

        return $result;
    }

}
