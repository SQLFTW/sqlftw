<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform;

class Mode extends \Dogma\IntSet
{

    public const ANSI_QUOTES = 1;
    public const IGNORE_SPACE = 2;
    public const NO_BACKSLASH_ESCAPES = 4;
    public const PIPES_AS_CONCAT = 8;
    public const REAL_AS_FLOAT = 16;

    // backward compatibility
    public const HIGH_NOT_PRECEDENCE = 32;

    public static function getAnsi(): self
    {
        return self::get(self::ANSI_QUOTES, self::IGNORE_SPACE, self::NO_BACKSLASH_ESCAPES, self::PIPES_AS_CONCAT, self::REAL_AS_FLOAT);
    }

}
