<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Index;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableAlgorithm;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableLock;
use SqlFtw\Sql\Ddl\Table\DdlTableCommand;
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\StatementImpl;

class CreateIndexCommand extends StatementImpl implements IndexCommand, DdlTableCommand
{

    public IndexDefinition $definition;

    public ?AlterTableAlgorithm $algorithm;

    public ?AlterTableLock $lock;

    public function __construct(
        IndexDefinition $definition,
        ?AlterTableAlgorithm $algorithm = null,
        ?AlterTableLock $lock = null
    ) {
        if ($definition->table === null) {
            throw new InvalidDefinitionException('Index must have a table.');
        }
        if ($definition->name === null) {
            throw new InvalidDefinitionException('Index must have a name.');
        }

        $this->definition = $definition;
        $this->algorithm = $algorithm;
        $this->lock = $lock;
    }

    public function getIndex(): ObjectIdentifier
    {
        /** @var string $name */
        $name = $this->definition->name;
        $table = $this->getTable();
        $schema = $table instanceof QualifiedName ? $table->schema : null;

        return $schema !== null ? new QualifiedName($name, $schema) : new SimpleName($name);
    }

    public function getTable(): ObjectIdentifier
    {
        /** @var ObjectIdentifier $table */
        $table = $this->definition->table;

        return $table;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE ';
        $result .= $this->definition->serializeHead($formatter);
        $result .= ' ON ' . $this->getTable()->serialize($formatter);
        $result .= ' ' . $this->definition->serializeTail($formatter);

        if ($this->algorithm !== null) {
            $result .= ' ALGORITHM ' . $this->algorithm->serialize($formatter);
        }
        if ($this->lock !== null) {
            $result .= ' LOCK ' . $this->lock->serialize($formatter);
        }

        return $result;
    }

}
