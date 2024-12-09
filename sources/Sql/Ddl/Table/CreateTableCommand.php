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
use SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition;
use SqlFtw\Sql\Ddl\Table\Option\TableOption;
use SqlFtw\Sql\Ddl\Table\Option\TableOptionsList;
use SqlFtw\Sql\Ddl\Table\Partition\PartitioningDefinition;
use SqlFtw\Sql\Dml\DuplicateOption;
use SqlFtw\Sql\Dml\Query\Query;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\StatementImpl;
use function is_array;

/**
 * @phpstan-import-type TableOptionValue from TableOption
 */
class CreateTableCommand extends StatementImpl implements AnyCreateTableCommand
{

    public ObjectIdentifier $name;

    /** @var non-empty-list<TableItem>|null */
    public ?array $items;

    public ?TableOptionsList $options;

    public ?PartitioningDefinition $partitioning;

    public bool $temporary;

    public bool $ifNotExists;

    public ?DuplicateOption $duplicateOption;

    public ?Query $query;

    public bool $startTransaction;

    /**
     * @param non-empty-list<TableItem>|null $items
     * @param TableOptionsList|array<TableOption::*, TableOptionValue>|null $options
     */
    public function __construct(
        ObjectIdentifier $name,
        ?array $items,
        $options = null,
        ?PartitioningDefinition $partitioning = null,
        bool $temporary = false,
        bool $ifNotExists = false,
        ?DuplicateOption $duplicateOption = null,
        ?Query $query = null,
        bool $startTransaction = false
    ) {
        if ($duplicateOption !== null && $query === null) {
            throw new InvalidDefinitionException('IGNORE/REPLACE can be uses only with CREATE TABLE AS ... command.');
        }

        $this->name = $name;
        $this->items = $items;
        $this->options = is_array($options) ? new TableOptionsList($options) : $options;
        $this->partitioning = $partitioning;
        $this->temporary = $temporary;
        $this->ifNotExists = $ifNotExists;
        $this->duplicateOption = $duplicateOption;
        $this->query = $query;
        $this->startTransaction = $startTransaction;
    }

    /**
     * @return list<ColumnDefinition>
     */
    public function getColumns(): array
    {
        if ($this->items === null) {
            return [];
        }
        $columns = [];
        foreach ($this->items as $item) {
            if ($item instanceof ColumnDefinition) {
                $columns[] = $item;
            }
        }

        return $columns;
    }

    /**
     * @return array<TableOption::*, TableOptionValue|null>
     */
    public function getOptions(): array
    {
        return $this->options !== null ? $this->options->options : [];
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE ';
        if ($this->temporary) {
            $result .= 'TEMPORARY ';
        }
        $result .= 'TABLE ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS ';
        }
        $result .= $this->name->serialize($formatter);

        if ($this->items !== null) {
            $result .= " (\n" . $formatter->indent . $formatter->formatSerializablesList($this->items, ",\n" . $formatter->indent) . "\n)";
        }

        if ($this->options !== null && !$this->options->isEmpty()) {
            $result .= ' ' . $this->options->serialize($formatter, ', ', ' ');
        }

        if ($this->partitioning !== null) {
            $result .= ' ' . $this->partitioning->serialize($formatter);
        }

        if ($this->duplicateOption !== null) {
            $result .= "\n" . $this->duplicateOption->serialize($formatter);
        }

        if ($this->query !== null) {
            $result .= "\nAS " . $this->query->serialize($formatter);
        }

        if ($this->startTransaction) {
            $result .= "\nSTART TRANSACTION";
        }

        return $result;
    }

}
