<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Column;

use SqlFtw\Sql\Keyword;

class ColumnAction extends \SqlFtw\Sql\SqlEnum
{

    public const ADD = Keyword::ADD;
    public const CHANGE = Keyword::CHANGE;
    public const MODIFY = Keyword::MODIFY;

}
