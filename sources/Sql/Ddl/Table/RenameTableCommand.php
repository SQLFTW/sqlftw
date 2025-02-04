<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use Dogma\CombineIterator;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\InvalidDefinitionException;
use function array_values;
use function count;
use function rtrim;

class RenameTableCommand extends Command implements DdlTablesCommand
{

    /** @var non-empty-list<Identifier&ObjectIdentifier> */
    public array $names;

    /** @var non-empty-list<Identifier&ObjectIdentifier> */
    public array $newNames;

    /**
     * @param non-empty-list<Identifier&ObjectIdentifier> $names
     * @param non-empty-list<Identifier&ObjectIdentifier> $newNames
     */
    public function __construct(array $names, array $newNames)
    {
        if (count($names) !== count($newNames)) {
            throw new InvalidDefinitionException('Count of old table names and new table names do not match.');
        }

        $this->names = array_values($names);
        $this->newNames = array_values($newNames);
    }

    /**
     * @param Identifier&ObjectIdentifier $table
     * @return (Identifier&ObjectIdentifier)|null
     */
    public function getNewNameForTable(Identifier $table): ?Identifier
    {
        /**
         * @var Identifier&ObjectIdentifier $old
         * @var Identifier&ObjectIdentifier $new
         */
        foreach ($this->getIterator() as $old => $new) {
            if ($old->name !== $table->name) {
                continue;
            }
            $oldSchema = $old instanceof QualifiedName ? $old->schema : null;
            $targetSchema = $new instanceof QualifiedName ? $new->schema : null;
            if ($oldSchema === null || $oldSchema === $targetSchema) {
                return $targetSchema === null ? new SimpleName($new->name) : $new;
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
