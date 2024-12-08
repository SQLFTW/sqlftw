<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

class CommonTableExpressionType extends SqlEnum
{

    public const WITH = Keyword::WITH;
    public const WITH_RECURSIVE = Keyword::WITH . ' ' . Keyword::RECURSIVE;

}
