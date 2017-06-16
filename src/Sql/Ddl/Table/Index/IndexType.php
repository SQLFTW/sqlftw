<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Index;

use SqlFtw\Sql\Keyword;

class IndexType extends \SqlFtw\Sql\SqlEnum
{

    public const PRIMARY = Keyword::PRIMARY . ' ' . Keyword::KEY;
    public const UNIQUE = Keyword::UNIQUE . ' ' . Keyword::KEY;
    public const INDEX = Keyword::KEY;
    public const FULLTEXT = Keyword::FULLTEXT . ' ' . Keyword::INDEX;
    public const SPATIAL = Keyword::SPATIAL . ' ' . Keyword::INDEX;

    public static function validateValue(string &$value): bool
    {
        $value = strtoupper($value);

        return parent::validateValue($value);
    }

}
