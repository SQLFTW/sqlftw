<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\StatementImpl;

class DropTableCommand extends StatementImpl implements DdlTablesCommand
{

    /** @var non-empty-list<ObjectIdentifier> */
    public array $names;

    public bool $temporary;

    public bool $ifExists;

    /** @var 'CANCADE'|'RESTRICT'|null */
    public ?string $action;

    /**
     * @param non-empty-list<ObjectIdentifier> $names
     * @param 'CANCADE'|'RESTRICT'|null $action
     */
    public function __construct(
        array $names,
        bool $temporary = false,
        bool $ifExists = false,
        ?string $action = null
    ) {
        $this->names = $names;
        $this->temporary = $temporary;
        $this->ifExists = $ifExists;
        $this->action = $action;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP ';
        if ($this->temporary) {
            $result .= 'TEMPORARY ';
        }
        $result .= 'TABLE ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }

        $result .= $formatter->formatSerializablesList($this->names);

        if ($this->action !== null) {
            $result .= ' ' . $this->action;
        }

        return $result;
    }

}
