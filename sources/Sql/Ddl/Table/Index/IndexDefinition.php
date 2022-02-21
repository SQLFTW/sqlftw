<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Index;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintBody;
use SqlFtw\Sql\Ddl\Table\TableItem;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\QualifiedName;
use function array_keys;
use function count;
use function is_int;
use function is_string;
use function sprintf;

class IndexDefinition implements TableItem, ConstraintBody
{
    use StrictBehaviorMixin;

    public const PRIMARY_KEY_NAME = null;

    /** @var string|null */
    private $name;

    /** @var IndexType */
    private $type;

    /** @var IndexColumn[] */
    private $columns;

    /** @var IndexAlgorithm|null */
    private $algorithm;

    /** @var IndexOptions|null */
    private $options;

    /** @var QualifiedName|null */
    private $table;

    /**
     * @param IndexColumn[]|int[]|string[]|null[] $columns
     */
    public function __construct(
        ?string $name,
        IndexType $type,
        array $columns,
        ?IndexAlgorithm $algorithm = null,
        ?IndexOptions $options = null,
        ?QualifiedName $table = null
    ) {
        if (count($columns) < 1) {
            throw new InvalidDefinitionException('Index must contain at least one column. None given.');
        }

        $this->name = $name;
        $this->type = $type;
        $this->setColumns($columns);
        $this->algorithm = $algorithm;
        $this->options = $options;
        $this->table = $table;
    }

    public function duplicateWithNewName(string $newName): self
    {
        $self = clone $this;
        $self->name = $newName;

        return $self;
    }

    public function duplicateWithVisibility(bool $visible): self
    {
        $self = clone $this;
        $self->options = $this->options !== null
            ? $this->options->duplicateWithVisibility($visible)
            : new IndexOptions(null, null, null, null, $visible);

        return $self;
    }

    public function duplicateAsPrimary(): self
    {
        $self = clone $this;
        $self->type = IndexType::get(IndexType::PRIMARY);
        $self->name = self::PRIMARY_KEY_NAME;

        return $self;
    }

    /**
     * @param IndexColumn[]|int[]|string[]|null[] $columns
     */
    private function setColumns(array $columns): void
    {
        $this->columns = [];
        foreach ($columns as $name => $column) {
            if ($column instanceof IndexColumn) {
                $this->addColumn($column->getName(), $column);
            } elseif (is_int($name) && is_string($column)) {
                $this->addColumn($column, new IndexColumn($column));
            } elseif (is_int($column)) {
                $this->addColumn($name, new IndexColumn($name, $column));
            } else {
                throw new InvalidDefinitionException("Invalid index column definition: $column($name)");
            }
        }
    }

    private function addColumn(string $columnName, IndexColumn $column): void
    {
        if (isset($this->columns[$columnName])) {
            throw new InvalidDefinitionException(sprintf('Column `%s` is already added to the index.', $columnName));
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

    public function getAlgorithm(): ?IndexAlgorithm
    {
        return $this->algorithm;
    }

    public function getOptions(): ?IndexOptions
    {
        return $this->options;
    }

    public function getTable(): ?QualifiedName
    {
        return $this->table;
    }

    /**
     * @return string[]
     */
    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    /**
     * @return IndexColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->serializeHead($formatter) . ' ' . $this->serializeTail($formatter);
    }

    public function serializeHead(Formatter $formatter): string
    {
        $result = $this->type->serialize($formatter);

        if ($this->name !== null) {
            $result .= ' ' . $formatter->formatName($this->name);
        }
        if ($this->algorithm !== null) {
            $result .= ' USING ' . $this->algorithm->serialize($formatter);
        }

        return $result;
    }

    public function serializeTail(Formatter $formatter): string
    {
        $result = '(' . $formatter->formatSerializablesList($this->columns) . ')';
        if ($this->options !== null) {
            $options = $this->options->serialize($formatter);
            if ($options !== '') {
                $result .= ' ' . $options;
            }
        }

        return $result;
    }

}
