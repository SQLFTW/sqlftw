<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;

/**
 * UNKNOWN
 */
class UnknownLiteral extends KeywordLiteral
{

    public string $value = 'UNKNOWN';

    public function serialize(Formatter $formatter): string
    {
        return 'UNKNOWN';
    }

}
