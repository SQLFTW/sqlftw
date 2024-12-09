<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition;
use function is_bool;
use function is_string;

class ModifyColumnAction implements ColumnAction
{

    public const FIRST = true;

    public ColumnDefinition $column;

    /** @var string|bool|null */
    public $after;

    /**
     * @param string|bool|null $after
     */
    public function __construct(ColumnDefinition $column, $after = null)
    {
        $this->column = $column;
        $this->after = $after;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'MODIFY COLUMN ' . $this->column->serialize($formatter);
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
