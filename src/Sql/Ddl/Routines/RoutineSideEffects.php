<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Routines;

use SqlFtw\Sql\Keyword;

class RoutineSideEffects extends \SqlFtw\Sql\SqlEnum
{

    public const CONTAINS_SQL = Keyword::CONTAINS . ' ' . Keyword::SQL;
    public const NO_SQL = Keyword::NO . ' ' . Keyword::SQL;
    public const READS_SQL_DATA = Keyword::READS . ' ' . Keyword::SQL . ' ' . Keyword::DATA;
    public const MODIFIES_SQL_DATA = Keyword::MODIFIES . ' ' . Keyword::SQL . ' ' . Keyword::DATA;

}
