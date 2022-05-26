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
use SqlFtw\Sql\Expression\QualifiedName;

class RenameToAction implements TableAction
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $newName;

    public function __construct(QualifiedName $newName)
    {
        $this->newName = $newName;
    }

    public function getNewName(): QualifiedName
    {
        return $this->newName;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'RENAME TO ' . $this->newName->serialize($formatter);
    }

}
