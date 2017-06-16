<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Trigger;

use SqlFtw\Sql\Keyword;

class TriggerOrder extends \SqlFtw\Sql\SqlEnum
{

    public const FOLLOWS = Keyword::FOLLOWS;
    public const PRECEDES = Keyword::PRECEDES;

}
