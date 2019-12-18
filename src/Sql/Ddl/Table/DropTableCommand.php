<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\MultipleTablesCommand;

class DropTableCommand implements MultipleTablesCommand, TableStructureCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName[] */
    private $tables;

    /** @var bool */
    private $temporary;

    /** @var bool */
    private $ifExists;

    /** @var bool|null */
    private $cascadeRestrict;

    /**
     * @param \SqlFtw\Sql\QualifiedName[] $tables
     * @param bool $temporary
     * @param bool $ifExists
     * @param bool|null $cascadeRestrict
     */
    public function __construct(
        array $tables,
        bool $temporary = false,
        bool $ifExists = false,
        ?bool $cascadeRestrict = null
    ) {
        $this->tables = $tables;
        $this->temporary = $temporary;
        $this->ifExists = $ifExists;
        $this->cascadeRestrict = $cascadeRestrict;
    }

    /**
     * @return \SqlFtw\Sql\QualifiedName[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function getTemporary(): bool
    {
        return $this->temporary;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function cascade(): bool
    {
        return $this->cascadeRestrict === true;
    }

    public function restrict(): bool
    {
        return $this->cascadeRestrict === false;
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

        $result .= $formatter->formatSerializablesList($this->tables);

        if ($this->cascadeRestrict === true) {
            $result .= ' CASCADE';
        } elseif ($this->cascadeRestrict === false) {
            $result .= ' RESTRICT';
        }

        return $result;
    }

}
