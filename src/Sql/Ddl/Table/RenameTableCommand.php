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
use SqlFtw\Sql\QualifiedName;
use function array_values;
use function count;
use function rtrim;

class RenameTableCommand implements DdlTablesCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName[] */
    protected $names;

    /** @var QualifiedName[] */
    private $newNames;

    /**
     * @param QualifiedName[] $names
     * @param QualifiedName[] $newTables
     */
    public function __construct(array $names, array $newTables)
    {
        Check::array($names, 1);
        Check::itemsOfType($names, QualifiedName::class);
        Check::array($newTables, 1);
        Check::itemsOfType($newTables, QualifiedName::class);
        if (count($names) !== count($newTables)) {
            throw new InvalidDefinitionException('Count of old table names and new table names do not match.');
        }

        $this->names = array_values($names);
        $this->newNames = array_values($newTables);
    }

    /**
     * @return QualifiedName[]
     */
    public function getNames(): array
    {
        return $this->names;
    }

    /**
     * @return QualifiedName[]
     */
    public function getNewNames(): array
    {
        return $this->newNames;
    }

    public function getNewNameForTable(QualifiedName $table): ?QualifiedName
    {
        /**
         * @var QualifiedName $old
         * @var QualifiedName $new
         */
        foreach ($this->getIterator() as $old => $new) {
            if ($old->getName() !== $table->getName()) {
                continue;
            }
            $oldSchema = $old->getSchema();
            $targetSchema = $new->getSchema();
            if ($oldSchema === null || $oldSchema === $targetSchema()) {
                return $new->getSchema() === null ? new QualifiedName($new->getName(), $targetSchema) : $new;
            }
        }

        return null;
    }

    public function getIterator(): CombineIterator
    {
        return new CombineIterator($this->names, $this->newNames);
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'RENAME TABLE';
        foreach ($this->names as $i => $table) {
            $result .= ' ' . $table->serialize($formatter) . ' TO ' . $this->newNames[$i]->serialize($formatter) . ',';
        }

        return rtrim($result, ',');
    }

}
