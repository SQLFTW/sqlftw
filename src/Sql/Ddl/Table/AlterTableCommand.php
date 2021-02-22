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
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AlterTableAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RenameToAction;
use SqlFtw\Sql\Ddl\Table\Alter\AlterActionsList;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableOption;
use SqlFtw\Sql\Ddl\Table\Option\TableOptionsList;
use SqlFtw\Sql\QualifiedName;
use function is_array;
use function rtrim;
use function trim;

class AlterTableCommand implements DdlTableCommand
{

    /** @var QualifiedName */
    private $name;

    /** @var AlterActionsList */
    private $actions;

    /** @var mixed[] */
    private $alterOptions;

    /** @var TableOptionsList|null */
    private $tableOptions;

    /**
     * @param QualifiedName $name
     * @param AlterActionsList|AlterTableAction[] $actions
     * @param mixed[] $alterOptions
     * @param TableOptionsList|mixed[]|null $tableOptions
     */
    public function __construct(
        QualifiedName $name,
        $actions = [],
        array $alterOptions = [],
        $tableOptions = null
    ) {
        Check::types($actions, [AlterActionsList::class, Type::PHP_ARRAY]);
        Check::types($tableOptions, [TableOptionsList::class, Type::PHP_ARRAY, Type::NULL]);
        if (is_array($alterOptions)) {
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
     * @return mixed[]
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
        return $this->actions->filter(RenameToAction::class)[0] ?? null;
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
                    $result .= "\n" . $formatter->indent . ($value ? 'WITH' : 'WITHOUT') . ' VALIDATION, ';
                } else {
                    $result .= "\n" . $formatter->indent . $option . ' ' . $formatter->formatValue($value) . ',';
                }
            }
        }

        return trim(rtrim($result, ' '), ',');
    }

}
