<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Column;

use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlEnum;

class GeneratedColumnType extends SqlEnum
{

    public const VIRTUAL = Keyword::VIRTUAL;
    public const STORED = Keyword::STORED;

}