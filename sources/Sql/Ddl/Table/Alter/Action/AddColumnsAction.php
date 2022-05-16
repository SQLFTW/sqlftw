<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition;
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;

class AddColumnsAction implements ColumnAction
{
    use StrictBehaviorMixin;

    public const FIRST = true;

    /** @var array<ColumnDefinition|IndexDefinition> */
    private $columns;

    /**
     * @param array<ColumnDefinition|IndexDefinition> $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * @return array<ColumnDefinition|IndexDefinition>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ADD COLUMN (' . $formatter->formatSerializablesList($this->columns) . ')';
    }

}
