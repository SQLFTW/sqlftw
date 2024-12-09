<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use SqlFtw\Error\Error;
use SqlFtw\Parser\TokenList;

/**
 * @property TokenList|null $tokenList
 * @property string|null $delimiter
 * @property list<string> $commentsBefore
 * @property list<Error> $errors
 */
interface Command extends Statement
{

}
