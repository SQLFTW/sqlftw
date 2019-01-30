<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

use Dogma\Enum\IntEnum;

class TableReferenceNodeType extends IntEnum
{

    public const TABLE = 1;
    public const PARENTHESES = 2;
    public const LIST = 3;
    public const SUBQUERY = 4;
    public const ESCAPED = 5;

    public const INNER_JOIN = 6;
    public const OUTER_JOIN = 7;
    public const NATURAL_JOIN = 8;
    public const STRAIGHT_JOIN = 9;

}
