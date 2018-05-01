<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Handler;

use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlEnum;

class HandlerReadTarget extends SqlEnum
{

    public const FIRST = Keyword::FIRST;
    public const LAST = Keyword::LAST;
    public const NEXT = Keyword::NEXT;
    public const PREV = Keyword::PREV;

    public const EQUAL = '=';
    public const LESS_OR_EQUAL = '<=';
    public const MORE_OR_EQUAL = '>=';
    public const LESS = '<';
    public const MORE = '>';

    /**
     * @return string[]
     */
    public static function getKeywords(): array
    {
        return [self::FIRST, self::LAST, self::NEXT, self::PREV];
    }

    /**
     * @return string[]
     */
    public static function getOperators(): array
    {
        return [self::EQUAL, self::LESS_OR_EQUAL, self::MORE_OR_EQUAL, self::LESS, self::MORE];
    }

    public function isKeyword(): bool
    {
        return in_array($this->getValue(), self::getKeywords());
    }

    public function isOperator(): bool
    {
        return in_array($this->getValue(), self::getOperators());
    }

}
