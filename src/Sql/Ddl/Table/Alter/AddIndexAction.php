<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;
use SqlFtw\SqlFormatter\SqlFormatter;

class AddIndexAction implements \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Ddl\Table\Index\IndexDefinition */
    private $index;

    public function __construct(IndexDefinition $index)
    {
        $this->index = $index;
    }

    public function getType(): AlterTableActionType
    {
        return AlterTableActionType::get(AlterTableActionType::ADD_INDEX);
    }

    public function getIndex(): IndexDefinition
    {
        return $this->index;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'ADD ' . $this->index->serialize($formatter);
    }

}
