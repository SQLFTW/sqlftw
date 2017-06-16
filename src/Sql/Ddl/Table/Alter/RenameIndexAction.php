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

class RenameIndexAction implements \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $oldName;

    /** @var string */
    private $newName;

    public function __construct(string $oldName, string $newName)
    {
        $this->oldName = $oldName;
        $this->newName = $newName;
    }

    public function getType(): AlterTableActionType
    {
        return AlterTableActionType::get(AlterTableActionType::RENAME_INDEX);
    }

    public function getOldName(): string
    {
        return $this->oldName;
    }

    public function getNewName(): string
    {
        return $this->newName;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'RENAME INDEX ' . $formatter->formatName($this->oldName) . ' TO ' . $formatter->formatName($this->newName);
    }

}
