<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use SqlFtw\SqlFormatter\SqlFormatter;

class DropTableCommand implements \SqlFtw\Sql\TablesCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\TableName[] */
    private $tables;

    /** @var bool */
    private $temporary;

    /** @var bool */
    private $ifExists;

    /**
     * @param \SqlFtw\Sql\Names\TableName[] $tables
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
     * @return \SqlFtw\Sql\Names\TableName[]
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

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'DROP';
        if ($this->temporary) {
            $result .= ' TEMPORARY';
        }
        $result .= ' TABLE ' . $formatter->formatSerializablesList($this->tables);

        return $result;
    }

}
