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

class SelectDistinctOption extends \SqlFtw\Sql\SqlEnum
{

    public const ALL = Keyword::ALL;
    public const DISTINCT = Keyword::DISTINCT;
    public const DISTINCT_ROW = Keyword::DISTINCTROW;

}
