<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

class NodeType extends \Dogma\IntEnum
{

    public const PARENTHESES = 1; // (exp)
    public const LIST = 2; // exp,exp,...
    public const LITERAL = 3; // 1, 'x', TRUE, NULL
    public const IDENTIFIER = 4; // `name`, @name
    public const PLACEHOLDER = 5; // ?
    //public const INTERVAL = 6;

    public const FUNCTION_CALL = 7; // fn(x,y,...)
    public const UNARY_OPERATOR = 8; // NOT x, -x
    public const BINARY_OPERATOR = 9; // x LIKE y, x + y
    public const TERNARY_OPERATOR = 10; // x BETWEEN y AND z, x LIKE y ESCAPE z
    //public const UNARY_SUBQUERY_OPERATOR = 11; // EXISTS (sub)
    //public const BINARY_SUBQUERY_OPERATOR = 12; // x > {ANY|ALL} (sub), x NOT IN (sub)
    public const SUBQUERY = 13; // (sub)
    public const MATCH_EXPRESSION = 14;
    public const CASE_EXPRESSION = 15;
    public const CURLY_EXPRESSION = 16; // {x expr}

}
