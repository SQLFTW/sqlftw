<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Constraint;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\TableName;

class ReferenceDefinition implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\TableName|null */
    private $sourceTable;

    /** @var string[] */
    private $sourceColumns;

    /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyAction|null */
    private $onUpdate;

    /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyAction|null */
    private $onDelete;

    /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyMatchType|null */
    private $matchType;

    /**
     * @param \SqlFtw\Sql\TableName $sourceTable
     * @param string[] $sourceColumns
     * @param \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyAction|null $onDelete
     * @param \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyAction|null $onUpdate
     * @param \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyMatchType|null $matchType
     */
    public function __construct(
        TableName $sourceTable,
        array $sourceColumns,
        ?ForeignKeyAction $onDelete = null,
        ?ForeignKeyAction $onUpdate = null,
        ?ForeignKeyMatchType $matchType = null
    ) {
        $this->sourceTable = $sourceTable;
        $this->sourceColumns = $sourceColumns;
        $this->onDelete = $onDelete;
        $this->onUpdate = $onUpdate;
        $this->matchType = $matchType;
    }

    public function getSourceTable(): TableName
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
        $result = ' REFERENCES ' . $this->sourceTable->serialize($formatter) . ' (' . $formatter->formatNamesList($this->sourceColumns) . ')';

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
