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
use Dogma\CombineIterator;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\MultipleTablesCommand;
use SqlFtw\Sql\QualifiedName;
use function array_values;
use function count;
use function rtrim;

class RenameTableCommand implements MultipleTablesCommand, TableStructureCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName[] */
    protected $tables;

    /** @var \SqlFtw\Sql\QualifiedName[] */
    private $newTables;

    /**
     * @param \SqlFtw\Sql\QualifiedName[] $tables
     * @param \SqlFtw\Sql\QualifiedName[] $newTables
     */
    public function __construct(array $tables, array $newTables)
    {
        Check::array($tables, 1);
        Check::itemsOfType($tables, QualifiedName::class);
        Check::array($newTables, 1);
        Check::itemsOfType($newTables, QualifiedName::class);
        if (count($tables) !== count($newTables)) {
            throw new InvalidDefinitionException('Count of old table names and new table names do not match.');
        }

        $this->tables = array_values($tables);
        $this->newTables = array_values($newTables);
    }

    /**
     * @return \SqlFtw\Sql\QualifiedName[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * @return \SqlFtw\Sql\QualifiedName[]
     */
    public function getNewTables(): array
    {
        return $this->newTables;
    }

    public function getNewNameForTable(QualifiedName $table): ?QualifiedName
    {
        /**
         * @var \SqlFtw\Sql\QualifiedName $old
         * @var \SqlFtw\Sql\QualifiedName $new
         */
        foreach ($this->getIterator() as $old => $new) {
            if ($old->getName() !== $table->getName()) {
                continue;
            }
            $oldSchema = $old->getSchema();
            $targetSchema = $new->getSchema();
            if ($oldSchema === $targetSchema() || $oldSchema === null) {
                return $new->getSchema() === null ? new QualifiedName($new->getName(), $targetSchema) : $new;
            }
        }
        return null;
    }

    public function getIterator(): CombineIterator
    {
        return new CombineIterator($this->tables, $this->newTables);
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
