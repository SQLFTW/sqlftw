<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Index;

use SqlFtw\Formatter\Formatter;

class IndexDefinition implements \SqlFtw\Sql\Ddl\Table\TableItem, \SqlFtw\Sql\Ddl\Table\Constraint\ConstraintBody
{
    use \Dogma\StrictBehaviorMixin;

    public const PRIMARY_KEY_NAME = null;

    /** @var string|null */
    private $name;

    /** @var \SqlFtw\Sql\Ddl\Table\Index\IndexType */
    private $type;

    /** @var \SqlFtw\Sql\Ddl\Table\Index\IndexColumn[] */
    private $columns;

    /** @var \SqlFtw\Sql\Ddl\Table\Index\IndexOptions */
    private $options;

    /**
     * @param string|null $name
     * @param \SqlFtw\Sql\Ddl\Table\Index\IndexType $type
     * @param \SqlFtw\Sql\Ddl\Table\Index\IndexColumn[]|int[]|string[]|null[] $columns
     * @param \SqlFtw\Sql\Ddl\Table\Index\IndexOptions $options
     */
    public function __construct(
        ?string $name,
        IndexType $type,
        array $columns,
        ?IndexOptions $options = null
    ) {
        if (count($columns) < 1) {
            throw new \SqlFtw\Sql\InvalidDefinitionException('Index must contain at least one column. None given.');
        }

        $this->name = $name;
        $this->type = $type;
        $this->setColumns($columns);
        $this->options = $options;
    }

    public function duplicateWithNewName(string $newName): self
    {
        $self = clone($this);
        $self->name = $newName;

        return $self;
    }

    public function duplicateAsPrimary(): self
    {
        $self = clone($this);
        $self->type = IndexType::get(IndexType::PRIMARY);
        $self->name = self::PRIMARY_KEY_NAME;

        return $self;
    }

    /**
     * @param \SqlFtw\Sql\Ddl\Table\Index\IndexColumn[]|int[]|string[]|null[] $columns
     */
    private function setColumns(array $columns): void
    {
        $this->columns = [];
        foreach ($columns as $name => $column) {
            if ($column instanceof IndexColumn) {
                $this->addColumn($column->getName(), $column);
            } elseif (is_int($name) && is_string($column)) {
                $this->addColumn($column, new IndexColumn($column));
            } else {
                $this->addColumn($name, new IndexColumn($name, $column));
            }
        }
    }

    private function addColumn(string $columnName, IndexColumn $column): void
    {
        if (isset($this->columns[$columnName])) {
            throw new \SqlFtw\Sql\InvalidDefinitionException(sprintf('Column `%s` is already added to the index.', $columnName));
        }
        $this->columns[$columnName] = $column;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): IndexType
    {
        return $this->type;
    }

    public function isPrimary(): bool
    {
        return $this->type->getValue() === IndexType::PRIMARY;
    }

    public function isUnique(): bool
    {
        return $this->type->getValue() === IndexType::UNIQUE;
    }

    public function isMultiColumn(): bool
    {
        return count($this->columns) > 1;
    }

    public function getOptions(): IndexOptions
    {
        return $this->options;
    }

    /**
     * @return string[]
     */
    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Table\Index\IndexColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->type->serialize($formatter);

        if ($this->name !== null) {
            $result .= ' ' . $formatter->formatName($this->name);
        }

        $result .= ' (' . $formatter->formatSerializablesList($this->columns) . ')';

        if (!$this->options->isEmpty()) {
            $result .= ' ' . $this->options->serialize($formatter);
        }

        return $result;
    }

}
