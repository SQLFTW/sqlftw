<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable Squiz.WhiteSpace.MemberVarSpacing.Incorrect

namespace SqlFtw\Parser;

use function substr;

/**
 * Represents atomic part of SQL syntax
 */
final class Token
{
    public const NORMALIZED_TYPES = TokenType::VALUES;

    // union of TokenType constants
    public int $type; // @phpstan-ignore property.uninitialized

    // length can be calculated using position of next token
    public int $start; // @phpstan-ignore property.uninitialized

    // normalized value. original value can be retrieved by position and length from parsed string
    public string $value; // @phpstan-ignore property.uninitialized

    public ?LexerException $exception = null;

    public function getSourceValue(string $source, ?Token $next): string
    {
        if ($next !== null) {
            return substr($source, $this->start, $next->start - $this->start);
        } else {
            return substr($source, $this->start);
        }
    }

}
