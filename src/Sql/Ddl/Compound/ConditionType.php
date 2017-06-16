<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use SqlFtw\Sql\Keyword;

class ConditionType extends \SqlFtw\Sql\SqlEnum
{

    public const ERROR = 'error';
    public const CONDITION = 'condition';
    public const SQL_STATE = Keyword::SQLSTATE;
    public const SQL_WARNING = Keyword::SQLWARNING;
    public const SQL_EXCEPTION = Keyword::SQLEXCEPTION;
    public const NOT_FOUND = Keyword::NOT . ' ' . Keyword::FOUND;

}
