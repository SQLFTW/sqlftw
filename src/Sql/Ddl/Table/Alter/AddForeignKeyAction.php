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
use SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition;

class AddForeignKeyAction implements AlterTableAction
{
    use StrictBehaviorMixin;

    /** @var ForeignKeyDefinition */
    private $foreignKey;

    public function __construct(ForeignKeyDefinition $foreignKey)
    {
        $this->foreignKey = $foreignKey;
    }

    public function getType(): AlterTableActionType
    {
        return AlterTableActionType::get(AlterTableActionType::ADD_FOREIGN_KEY);
    }

    public function getForeignKey(): ForeignKeyDefinition
    {
        return $this->foreignKey;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ADD ' . $this->foreignKey->serialize($formatter);
    }

}
