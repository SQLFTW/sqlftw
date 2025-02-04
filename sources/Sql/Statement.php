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

abstract class Statement extends Node
{

    public ?TokenList $tokenList = null;

    public ?string $delimiter = null;

    /** @var list<string> */
    public array $commentsBefore = [];

    /** @var list<Error> */
    public array $errors = [];

}
