<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Constraint;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\QualifiedName;
use function count;

class ForeignKeyDefinition implements ConstraintBody
{
    use StrictBehaviorMixin;

    /** @var string[] */
    private $columns;

    /** @var \SqlFtw\Sql\QualifiedName|null */
    private $sourceTable;

    /** @var string[] */
    private $sourceColumns;

    /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyAction|null */
    private $onUpdate;

    /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyAction|null */
    private $onDelete;

    /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyMatchType|null */
    private $matchType;

    /** @var string|null */
    private $indexName;

    /**
     * @param string[] $columns
     * @param \SqlFtw\Sql\QualifiedName $sourceTable
     * @param string[] $sourceColumns
     * @param \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyAction|null $onDelete
     * @param \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyAction|null $onUpdate
     * @param \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyMatchType|null $matchType
     * @param string|null $indexName
     */
    public function __construct(
        array $columns,
        QualifiedName $sourceTable,
        array $sourceColumns,
        ?ForeignKeyAction $onDelete = null,
        ?ForeignKeyAction $onUpdate = null,
        ?ForeignKeyMatchType $matchType = null,
        ?string $indexName = null
    ) {
        if (count($columns) < 1 || count($sourceColumns) < 1) {
            throw new InvalidDefinitionException('List of columns and source columns must not be empty.');
        }
        if (count($columns) !== count($sourceColumns)) {
            throw new InvalidDefinitionException('Number of foreign key columns and source columns does not match.');
        }

        $this->columns = $columns;
        $this->sourceTable = $sourceTable;
        $this->sourceColumns = $sourceColumns;
        $this->onDelete = $onDelete;
        $this->onUpdate = $onUpdate;
        $this->matchType = $matchType;
        $this->indexName = $indexName;
    }

    /**
     * @param string[] $columns
     * @param \SqlFtw\Sql\Ddl\Table\Constraint\ReferenceDefinition $reference
     * @param string|null $indexName
     * @return self
     */
    public static function createFromReference(array $columns, ReferenceDefinition $reference, ?string $indexName = null): self
    {
        return new self(
            $columns,
            $reference->getSourceTable(),
            $reference->getSourceColumns(),
            $reference->getOnDelete(),
            $reference->getOnUpdate(),
            $reference->getMatchType(),
            $indexName
        );
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getSourceTable(): QualifiedName
    {
        return $this->sourceTable;
    }

    /**
     * @return string[]
     */
    public function getSourceColumns(): array
    {
        return $this->sourceColumns;
    }

    public function getOnDelete(): ForeignKeyAction
    {
        return $this->onDelete;
    }

    public function setOnDelete(ForeignKeyAction $action): void
    {
        $this->onDelete = $action;
    }

    public function getOnUpdate(): ForeignKeyAction
    {
        return $this->onUpdate;
    }

    public function setOnUpdate(ForeignKeyAction $action): void
    {
        $this->onUpdate = $action;
    }

    public function getMatchType(): ?ForeignKeyMatchType
    {
        return $this->matchType;
    }

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'FOREIGN KEY';
        if ($this->indexName !== null) {
            $result .= ' ' . $formatter->formatName($this->indexName);
        }
        $result .= ' (' . $formatter->formatNamesList($this->columns) . ')';
        $result .= ' REFERENCES ' . $this->getSourceTable()->serialize($formatter) . ' (' . $formatter->formatNamesList($this->sourceColumns) . ')';

        if ($this->matchType !== null) {
            $result .= ' MATCH ' . $this->matchType->serialize($formatter);
        }
        if ($this->onDelete !== null) {
            $result .= ' ON DELETE ' . $this->onDelete->serialize($formatter);
        }
        if ($this->onUpdate !== null) {
            $result .= ' ON UPDATE ' . $this->onUpdate->serialize($formatter);
        }
        return $result;
    }

}
