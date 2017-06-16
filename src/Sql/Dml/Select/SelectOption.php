<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Select;

use SqlFtw\Sql\Keyword;

class SelectOption extends \SqlFtw\Sql\SqlEnum
{

    public const HIGH_PRIORITY = Keyword::HIGH_PRIORITY;
    public const STRAIGHT_JOIN = Keyword::STRAIGHT_JOIN;
    public const SMALL_RESULT = Keyword::SQL_SMALL_RESULT;
    public const BIG_RESULT = Keyword::SQL_BIG_RESULT;
    public const BUFFER_RESULT = Keyword::SQL_BUFFER_RESULT;
    public const CACHE = Keyword::SQL_CACHE;
    public const NO_CACHE = Keyword::SQL_NO_CACHE;
    public const CALC_FOUND_ROWS = Keyword::SQL_CALC_FOUND_ROWS;

}
