<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;

class AddIndexAction implements AlterTableAction
{
    use StrictBehaviorMixin;

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

    public function serialize(Formatter $formatter): string
    {
        return 'ADD ' . $this->index->serialize($formatter);
    }

}
