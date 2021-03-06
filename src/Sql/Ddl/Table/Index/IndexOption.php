<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Index;

use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlEnum;

class IndexOption extends SqlEnum
{

    public const KEY_BLOCK_SIZE = Keyword::KEY_BLOCK_SIZE;
    public const WITH_PARSER = Keyword::WITH . ' ' . Keyword::PARSER;
    public const COMMENT = Keyword::COMMENT;
    public const VISIBLE = Keyword::VISIBLE;
    public const MERGE_THRESHOLD = 'MERGE_THRESHOLD';

}
