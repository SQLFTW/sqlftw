<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/paranoiq/dogma)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Option;

use SqlFtw\Sql\Keyword;

class TableInsertMethod extends \SqlFtw\Sql\SqlEnum
{

    public const NO = Keyword::NO;
    public const FIRST = Keyword::FIRST;
    public const LAST = Keyword::LAST;

}
