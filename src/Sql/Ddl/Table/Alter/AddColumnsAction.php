<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use SqlFtw\SqlFormatter\SqlFormatter;

class AddColumnsAction implements \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction
{
    use \Dogma\StrictBehaviorMixin;

    public const FIRST = true;

    /** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] */
    private $columns;

    /**
     * @param \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    public function getType(): AlterTableActionType
    {
        return AlterTableActionType::get(AlterTableActionType::ADD_COLUMNS);
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'ADD COLUMN (' . $formatter->formatSerializablesList($this->columns) . ')';
    }

}
