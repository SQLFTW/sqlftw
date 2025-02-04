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
 * used in: GET_FORMAT({DATE|TIME|DATETIME}, {'EUR'|'USA'|'JIS'|'ISO'|'INTERNAL'})
 */
class TimeTypeLiteral extends KeywordLiteral
{

    /** @var 'DATE'|'TIME'|'DATETIME' */
    public string $value; // @phpstan-ignore property.phpDocType

    /**
     * @param 'DATE'|'TIME'|'DATETIME' $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->value;
    }

}
