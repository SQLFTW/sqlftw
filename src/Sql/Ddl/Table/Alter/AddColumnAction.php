<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition;

class AddColumnAction implements \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction
{
    use \Dogma\StrictBehaviorMixin;

    public const FIRST = true;

    /** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition */
    private $column;

    /** @var string|bool|null */
    private $after;

    /**
     * @param \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition $column
     * @param string|bool|null $after
     */
    public function __construct(ColumnDefinition $column, $after = null)
    {
        $this->column = $column;
        $this->after = $after;
    }

    public function getType(): AlterTableActionType
    {
        return AlterTableActionType::get(AlterTableActionType::ADD_COLUMN);
    }

    public function getColumn(): ColumnDefinition
    {
        return $this->column;
    }

    public function isFirst(): bool
    {
        return $this->after === self::FIRST;
    }

    public function getAfter(): ?string
    {
        return is_bool($this->after) ? null : $this->after;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ADD COLUMN ' . $this->column->serialize($formatter);
        if ($this->after !== null) {
            if (is_string($this->after)) {
                $result .= ' AFTER ' . $formatter->formatName($this->after);
            } else {
                $result .= ' FIRST';
            }
        }

        return $result;
    }

}
