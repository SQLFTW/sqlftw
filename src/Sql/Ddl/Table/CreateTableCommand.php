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
use Dogma\StrictBehaviorMixin;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Option\TableOptionsList;
use SqlFtw\Sql\Ddl\Table\Partition\PartitioningDefinition;
use SqlFtw\Sql\Dml\DuplicateOption;
use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\Sql\QualifiedName;

class CreateTableCommand implements AnyCreateTableCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName */
    private $table;

    /** @var \SqlFtw\Sql\Ddl\Table\TableItem[] */
    private $items;

    /** @var \SqlFtw\Sql\Ddl\Table\Option\TableOptionsList  */
    private $options;

    /** @var \SqlFtw\Sql\Ddl\Table\Partition\PartitioningDefinition|null */
    private $partitioning;

    /** @var bool */
    private $temporary;

    /** @var bool */
    private $ifNotExists;

    /** @var \SqlFtw\Sql\Dml\DuplicateOption|null */
    private $duplicateOption;

    /** @var \SqlFtw\Sql\Dml\Select\SelectCommand|null */
    private $select;

    /**
     * @param \SqlFtw\Sql\QualifiedName $table
     * @param \SqlFtw\Sql\Ddl\Table\TableItem[] $items
     * @param \SqlFtw\Sql\Ddl\Table\Option\TableOptionsList|mixed[]|null $options
     * @param \SqlFtw\Sql\Ddl\Table\Partition\PartitioningDefinition|null $partitioning
     * @param bool $temporary
     * @param bool $ifNotExists
     * @param \SqlFtw\Sql\Dml\DuplicateOption|null $duplicateOption
     * @param \SqlFtw\Sql\Dml\Select\SelectCommand|null $select
     */
    public function __construct(
        QualifiedName $table,
        array $items,
        $options = null,
        ?PartitioningDefinition $partitioning = null,
        bool $temporary = false,
        bool $ifNotExists = false,
        ?DuplicateOption $duplicateOption = null,
        ?SelectCommand $select = null
    ) {
        Check::types($options, [TableOptionsList::class, Type::PHP_ARRAY, Type::NULL]);
        if ($duplicateOption !== null && $select === null) {
            throw new \SqlFtw\Sql\InvalidDefinitionException('IGNORE/REPLACE can be uses only with CREATE TABLE AS ... command.');
        }

        $this->table = $table;
        $this->items = $items;
        $this->options = is_array($options) ? new TableOptionsList($options) : $options;
        $this->partitioning = $partitioning;
        $this->temporary = $temporary;
        $this->ifNotExists = $ifNotExists;
        $this->duplicateOption = $duplicateOption;
        $this->select = $select;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Table\TableItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getOptions(): ?TableOptionsList
    {
        return $this->options;
    }

    public function getPartitioning(): ?PartitioningDefinition
    {
        return $this->partitioning;
    }

    public function isTemporary(): bool
    {
        return $this->temporary;
    }

    public function ifNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function getDuplicateOption(): ?DuplicateOption
    {
        return $this->duplicateOption;
    }

    public function getSelect(): ?SelectCommand
    {
        return $this->select;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE ';
        if ($this->temporary) {
            $result .= 'TEMPORARY ';
        }
        $result .= 'TABLE ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS';
        }
        $result .= $this->table->serialize($formatter);

        if ($this->items !== null) {
            $result .= " (\n" . $formatter->indent . $formatter->formatSerializablesList($this->items, ",\n" . $formatter->indent) . "\n)";
        }

        if ($this->options !== null && !$this->options->isEmpty()) {
            $result .= ' ' . $this->options->serialize($formatter, ', ', ' ');
        }

        if ($this->partitioning) {
            $result .= $this->partitioning->serialize($formatter);
        }

        if ($this->duplicateOption) {
            $result .= "\n" . $this->duplicateOption->serialize($formatter);
        }

        if ($this->select !== null) {
            $result .= "\nAS " . $this->select->serialize($formatter);
        }

        return $result;
    }

}
