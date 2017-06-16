<?php
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
use SqlFtw\Sql\Ddl\Table\Alter\AlterActionsList;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableOption;
use SqlFtw\Sql\Ddl\Table\Option\TableOptionsList;
use SqlFtw\Sql\Names\TableName;
use SqlFtw\SqlFormatter\SqlFormatter;

class AlterTableCommand implements \SqlFtw\Sql\Command
{

    /** @var \SqlFtw\Sql\Names\TableName */
    private $table;

    /** @var \SqlFtw\Sql\Ddl\Table\Alter\AlterActionsList */
    private $actions;

    /** @var mixed[] */
    private $alterOptions;

    /** @var \SqlFtw\Sql\Ddl\Table\Option\TableOptionsList|mixed[] */
    private $tableOptions;

    /**
     * @param \SqlFtw\Sql\Names\TableName $table
     * @param \SqlFtw\Sql\Ddl\Table\Alter\AlterActionsList|\SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction[] $actions
     * @param mixed[] $alterOptions
     * @param \SqlFtw\Sql\Ddl\Table\Option\TableOptionsList|mixed[] $tableOptions
     */
    public function __construct(
        TableName $table,
        $actions = [],
        $alterOptions = [],
        $tableOptions = []
    ) {
        Check::types($actions, [AlterActionsList::class, Type::PHP_ARRAY, Type::NULL]);
        Check::types($tableOptions, [TableOptionsList::class, Type::PHP_ARRAY, Type::NULL]);
        if (is_array($alterOptions)) {
            foreach ($alterOptions as $option => $value) {
                AlterTableOption::get($option);
            }
        }

        $this->table = $table;
        $this->actions = is_array($actions) ? new AlterActionsList($actions) : $actions;
        $this->alterOptions = $alterOptions;
        $this->tableOptions = is_array($tableOptions) ? new TableOptionsList($tableOptions) : $tableOptions;
    }

    public function getTable(): TableName
    {
        return $this->table;
    }

    public function getActions(): AlterActionsList
    {
        return $this->actions;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'ALTER TABLE ' . $this->table->serialize($formatter) . "\n";

        $result .= $this->actions->serialize($formatter);

        if ($this->tableOptions !== null) {
            $result .= ",\n" . $this->tableOptions->serialize($formatter, ",\n", ' = ');
        }

        if ($this->alterOptions !== null) {
            foreach ($this->alterOptions as $option => $value) {
                if ($option === AlterTableOption::FORCE) {
                    $result .= "\nFORCE";
                } else {
                    $result .= ",\n" . $option . ' = ' . $formatter->formatValue($value);
                }
            }
        }

        return $result;
    }

}
