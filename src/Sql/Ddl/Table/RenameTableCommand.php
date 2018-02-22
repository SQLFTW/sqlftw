<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use Dogma\Check;
use Dogma\ZipIterator;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\TableName;

class RenameTableCommandMultiple implements \SqlFtw\Sql\MultipleTablesCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\TableName[] */
    protected $tables;

    /** @var \SqlFtw\Sql\TableName[] */
    private $newTables;

    /**
     * @param \SqlFtw\Sql\TableName[] $tables
     * @param \SqlFtw\Sql\TableName[] $newTables
     */
    public function __construct(array $tables, array $newTables)
    {
        Check::array($tables, 1);
        Check::itemsOfType($tables, TableName::class);
        Check::array($newTables, 1);
        Check::itemsOfType($newTables, TableName::class);
        if (count($tables) !== count($newTables)) {
            throw new \SqlFtw\Sql\InvalidDefinitionException('Count of old table names and new table names do not match.');
        }

        $this->tables = array_values($tables);
        $this->newTables = array_values($newTables);
    }

    /**
     * @return \SqlFtw\Sql\TableName[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * @return \SqlFtw\Sql\TableName[]
     */
    public function getNewTables(): array
    {
        return $this->newTables;
    }

    public function getIterator(): ZipIterator
    {
        return new ZipIterator($this->tables, $this->newTables);
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'RENAME TABLE';
        foreach ($this->tables as $i => $table) {
            $result .= ' ' . $table->serialize($formatter) . ' TO ' . $this->newTables[$i]->serialize($formatter) . ',';
        }

        return rtrim($result, ',');
    }

}
