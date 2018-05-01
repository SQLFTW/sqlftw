<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use SqlFtw\Formatter\Formatter;

class DropTableCommand implements \SqlFtw\Sql\MultipleTablesCommand, \SqlFtw\Sql\Ddl\Table\TableStructureCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName[] */
    private $tables;

    /** @var bool */
    private $temporary;

    /** @var bool */
    private $ifExists;

    /**
     * @param \SqlFtw\Sql\QualifiedName[] $tables
     * @param bool $temporary
     * @param bool $ifExists
     */
    public function __construct(array $tables, bool $temporary = false, bool $ifExists = false)
    {
        $this->tables = $tables;
        $this->temporary = $temporary;
        $this->ifExists = $ifExists;
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

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP';
        if ($this->temporary) {
            $result .= ' TEMPORARY';
        }
        $result .= ' TABLE ' . $formatter->formatSerializablesList($this->tables);

        return $result;
    }

}
