<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AlterTableAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RenameToAction;
use SqlFtw\Sql\Ddl\Table\Alter\AlterActionsList;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableAlgorithm;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableLock;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableOption;
use SqlFtw\Sql\Ddl\Table\Option\TableOptionsList;
use SqlFtw\Sql\QualifiedName;
use function assert;
use function is_array;
use function is_bool;
use function rtrim;
use function trim;

/**
 * @phpstan-import-type TableOptionValue from TableOptionsList
 */
class AlterTableCommand implements DdlTableCommand
{

    /** @var QualifiedName */
    private $name;

    /** @var AlterActionsList */
    private $actions;

    /** @var array<string, bool|AlterTableLock|AlterTableAlgorithm> */
    private $alterOptions;

    /** @var TableOptionsList|null */
    private $tableOptions;

    /**
     * @param AlterActionsList|AlterTableAction[] $actions
     * @param array<string, bool|AlterTableLock|AlterTableAlgorithm> $alterOptions
     * @param TableOptionsList|array<TableOptionValue>|null $tableOptions
     */
    public function __construct(
        QualifiedName $name,
        $actions = [],
        array $alterOptions = [],
        $tableOptions = null
    ) {
        if ($alterOptions !== []) {
            foreach ($alterOptions as $option => $value) {
                AlterTableOption::get($option);
            }
        }

        $this->name = $name;
        $this->actions = is_array($actions) ? new AlterActionsList($actions) : $actions;
        $this->alterOptions = $alterOptions;
        $this->tableOptions = is_array($tableOptions) ? new TableOptionsList($tableOptions) : $tableOptions;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getActions(): AlterActionsList
    {
        return $this->actions;
    }

    /**
     * @return array<string, bool|AlterTableLock|AlterTableAlgorithm>
     */
    public function getAlterOptions(): array
    {
        return $this->alterOptions;
    }

    public function getTableOptions(): ?TableOptionsList
    {
        return $this->tableOptions;
    }

    public function getRenameAction(): ?RenameToAction
    {
        /** @var RenameToAction|null $rename */
        $rename = $this->actions->filter(RenameToAction::class)[0] ?? null;

        return $rename;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER TABLE ' . $this->name->serialize($formatter);

        $result .= $this->actions->serialize($formatter);

        if ($this->tableOptions !== null && !$this->actions->isEmpty()) {
            $result .= ',';
        }

        if ($this->tableOptions !== null && !$this->tableOptions->isEmpty()) {
            $result .= "\n" . $formatter->indent . $this->tableOptions->serialize($formatter, ",\n", ' ');
        }

        $result = rtrim($result, ',');

        if ($this->alterOptions !== null) {
            foreach ($this->alterOptions as $option => $value) {
                if ($option === AlterTableOption::FORCE) {
                    $result .= "\n" . $formatter->indent . 'FORCE, ';
                } elseif ($option === AlterTableOption::VALIDATION) {
                    assert(is_bool($value));
                    $result .= "\n" . $formatter->indent . ($value ? 'WITH' : 'WITHOUT') . ' VALIDATION, ';
                } else {
                    $result .= "\n" . $formatter->indent . $option . ' ' . $formatter->formatValue($value) . ',';
                }
            }
        }

        return trim(rtrim($result, ' '), ',');
    }

}
