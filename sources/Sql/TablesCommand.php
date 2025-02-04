<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use SqlFtw\Sql\Expression\ObjectIdentifier;

abstract class TablesCommand extends Command
{

    /** @var non-empty-list<ObjectIdentifier> */
    public array $tables; // @phpstan-ignore property.uninitialized

}
