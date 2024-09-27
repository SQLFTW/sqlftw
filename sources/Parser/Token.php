<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

/**
 * Represents atomic part of SQL syntax
 */
final class Token
{

    // union of TokenType constants
    public int $type; // @phpstan-ignore property.uninitialized

    // length can be calculated using position of next token
    public int $start; // @phpstan-ignore property.uninitialized

    // normalized value. original value can be retrieved by position and length from parsed string
    public string $value; // @phpstan-ignore property.uninitialized

    /** @deprecated */
    public ?string $original = null;

    public ?LexerException $exception = null;

}
