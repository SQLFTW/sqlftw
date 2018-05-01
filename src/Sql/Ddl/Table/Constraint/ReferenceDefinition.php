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
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\SqlSerializable;

class ReferenceDefinition implements SqlSerializable
{
    use StrictBehaviorMixin;

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

    /**
     * @param \SqlFtw\Sql\QualifiedName $sourceTable
     * @param string[] $sourceColumns
     * @param \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyAction|null $onDelete
     * @param \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyAction|null $onUpdate
     * @param \SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyMatchType|null $matchType
     */
    public function __construct(
        QualifiedName $sourceTable,
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
