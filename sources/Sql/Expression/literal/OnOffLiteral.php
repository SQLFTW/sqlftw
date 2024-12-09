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
use SqlFtw\Sql\Keyword;

/**
 * ON, OFF
 */
class OnOffLiteral implements BoolValue, KeywordLiteral
{

    public string $value;

    /**
     * @param 'ON'|'OFF' $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function asBool(): bool
    {
        return $this->value === Keyword::ON;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->value;
    }

}
