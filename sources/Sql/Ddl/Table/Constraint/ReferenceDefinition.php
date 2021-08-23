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
use SqlFtw\Sql\SqlSerializable;
use function count;

class ReferenceDefinition implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var QualifiedName|null */
    private $sourceTable;

    /** @var string[] */
    private $sourceColumns;

    /** @var ForeignKeyAction|null */
    private $onUpdate;

    /** @var ForeignKeyAction|null */
    private $onDelete;

    /** @var ForeignKeyMatchType|null */
    private $matchType;

    /**
     * @param QualifiedName $sourceTable
     * @param string[] $sourceColumns
     * @param ForeignKeyAction|null $onDelete
     * @param ForeignKeyAction|null $onUpdate
     * @param ForeignKeyMatchType|null $matchType
     */
    public function __construct(
        QualifiedName $sourceTable,
        array $sourceColumns,
        ?ForeignKeyAction $onDelete = null,
        ?ForeignKeyAction $onUpdate = null,
        ?ForeignKeyMatchType $matchType = null
    ) {
        if (count($sourceColumns) < 1) {
            throw new InvalidDefinitionException('List of source columns must not be empty.');
        }

        $this->sourceTable = $sourceTable;
        $this->sourceColumns = $sourceColumns;
        $this->onDelete = $onDelete;
        $this->onUpdate = $onUpdate;
        $this->matchType = $matchType;
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

    public function getOnDelete(): ?ForeignKeyAction
    {
        return $this->onDelete;
    }

    public function setOnDelete(ForeignKeyAction $action): void
    {
        $this->onDelete = $action;
    }

    public function getOnUpdate(): ?ForeignKeyAction
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

    public function serialize(Formatter $formatter): string
    {
        $result = 'REFERENCES ' . $this->sourceTable->serialize($formatter) . ' (' . $formatter->formatNamesList($this->sourceColumns) . ')';

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
