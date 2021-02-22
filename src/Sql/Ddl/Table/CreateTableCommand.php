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
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\QualifiedName;
use function is_array;

class CreateTableCommand implements AnyCreateTableCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var TableItem[] */
    private $items;

    /** @var TableOptionsList */
    private $options;

    /** @var PartitioningDefinition|null */
    private $partitioning;

    /** @var bool */
    private $temporary;

    /** @var bool */
    private $ifNotExists;

    /** @var DuplicateOption|null */
    private $duplicateOption;

    /** @var SelectCommand|null */
    private $select;

    /**
     * @param QualifiedName $name
     * @param TableItem[] $items
     * @param TableOptionsList|mixed[]|null $options
     * @param PartitioningDefinition|null $partitioning
     * @param bool $temporary
     * @param bool $ifNotExists
     * @param DuplicateOption|null $duplicateOption
     * @param SelectCommand|null $select
     */
    public function __construct(
        QualifiedName $name,
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
            throw new InvalidDefinitionException('IGNORE/REPLACE can be uses only with CREATE TABLE AS ... command.');
        }

        $this->name = $name;
        $this->items = $items;
        $this->options = is_array($options) ? new TableOptionsList($options) : $options;
        $this->partitioning = $partitioning;
        $this->temporary = $temporary;
        $this->ifNotExists = $ifNotExists;
        $this->duplicateOption = $duplicateOption;
        $this->select = $select;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    /**
     * @return TableItem[]
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
        $result .= $this->name->serialize($formatter);

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
