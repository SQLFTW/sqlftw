<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

/**
 * Literals representing a concrete value or acting in place of value known at compile time (e.g. DEFAULT)
 *
 * e.g. 1, 'x', TRUE, NULL, DEFAULT, MAXVALUE...
 */
abstract class Literal extends RootNode
{

    public string $value; // @phpstan-ignore property.uninitialized

}
