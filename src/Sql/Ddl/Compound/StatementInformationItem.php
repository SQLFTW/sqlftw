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

class StatementInformationItem extends \SqlFtw\Sql\SqlEnum implements \SqlFtw\Sql\Ddl\Compound\InformationItem
{

    public const NUMBER = Keyword::NUMBER;
    public const ROW_COUNT = Keyword::ROW_COUNT;

}
