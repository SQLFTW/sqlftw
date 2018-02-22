<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Sql\Keyword;

class TimeIntervalUnit extends \SqlFtw\Sql\SqlEnum
{
    use \Dogma\StrictBehaviorMixin;

    public const MICROSECOND = Keyword::MICROSECOND;
    public const SECOND = Keyword::SECOND;
    public const MINUTE = Keyword::MINUTE;
    public const HOUR = Keyword::HOUR;
    public const DAY = Keyword::DAY;
    public const WEEK = Keyword::WEEK;
    public const MONTH = Keyword::MONTH;
    public const QUARTER = Keyword::QUARTER;
    public const YEAR = Keyword::YEAR;

    public const SECOND_MICROSECOND = Keyword::SECOND_MICROSECOND;
    public const MINUTE_MICROSECOND = Keyword::MINUTE_MICROSECOND;
    public const MINUTE_SECOND = Keyword::MINUTE_SECOND;
    public const HOUR_MICROSECOND = Keyword::HOUR_MICROSECOND;
    public const HOUR_SECOND = Keyword::HOUR_SECOND;
    public const HOUR_MINUTE = Keyword::HOUR_MINUTE;
    public const DAY_MICROSECOND = Keyword::DAY_MICROSECOND;
    public const DAY_SECOND = Keyword::DAY_SECOND;
    public const DAY_MINUTE = Keyword::DAY_MINUTE;
    public const DAY_HOUR = Keyword::DAY_HOUR;
    public const YEAR_MONTH = Keyword::YEAR_MONTH;

}
