<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Option;

use SqlFtw\Sql\Keyword;

class TableRowFormat extends \SqlFtw\Sql\SqlEnum
{

    public const DEFAULT = Keyword::DEFAULT;
    public const DYNAMIC = Keyword::DYNAMIC;
    public const FIXED = Keyword::FIXED;
    public const COMPRESSED = Keyword::COMPRESSED;
    public const REDUNDANT = Keyword::REDUNDANT;
    public const COMPACT = Keyword::COMPACT;

}
