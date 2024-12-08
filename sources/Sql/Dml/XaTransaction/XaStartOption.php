<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\XaTransaction;

use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlEnum;

class XaStartOption extends SqlEnum
{

    public const JOIN = Keyword::JOIN;
    public const RESUME = Keyword::RESUME;

}
