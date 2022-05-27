<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\Enum\IntSet;
use Dogma\Math\PowersOfTwo;

class TokenType extends IntSet
{

    /** Space, \t, \r, \n */
    public const WHITESPACE = PowersOfTwo::_1;

    /** Any comment */
    public const COMMENT = PowersOfTwo::_2;

    /** /* ... * / (standard) */
    public const BLOCK_COMMENT = PowersOfTwo::_4;

    /** /*!50701 ... * / (MySQL) */
    public const OPTIONAL_COMMENT = PowersOfTwo::_8;

    /** /*+ ... * / (MySQL) */
    public const HINT_COMMENT = PowersOfTwo::_16;

    /** -- ... (standard) */
    public const DOUBLE_HYPHEN_COMMENT = PowersOfTwo::_32;

    /** // ... (not standard) */
    public const DOUBLE_SLASH_COMMENT = PowersOfTwo::_64;

    /** # ... (not standard) */
    public const HASH_COMMENT = PowersOfTwo::_128;

    /** Any name (quoted string or unquoted string other than a keyword) */
    public const NAME = PowersOfTwo::_256;

    /** Variable name starting with "@" */
    public const AT_VARIABLE = PowersOfTwo::_512;

    /** Unquoted string consisting of letters, numbers and "_" */
    public const UNQUOTED_NAME = PowersOfTwo::_1K;

    /** Unquoted keyword recognized by given platform */
    public const KEYWORD = PowersOfTwo::_2K;

    /** Unquoted reserved keyword recognized by given platform */
    public const RESERVED = PowersOfTwo::_4K;

    /** Any value (string, number, boolean...) */
    public const VALUE = PowersOfTwo::_8K;

    /** Quoted string value (not name) */
    public const STRING = PowersOfTwo::_16K;

    /** '...' - a string literal */
    public const SINGLE_QUOTED_STRING = PowersOfTwo::_32K;

    /** "..." - a name (standard) or a string literal (not standard, MySQL) */
    public const DOUBLE_QUOTED_STRING = PowersOfTwo::_64K;

    /** `...` - a name (not standard, MySQL, SqLite, PostgreSQL) */
    public const BACKTICK_QUOTED_STRING = PowersOfTwo::_128K;

    /** [...] - a name (not standard, MSSQL, SqLite) */
    public const SQUARE_BRACKETED_STRING = PowersOfTwo::_256K;

    /** Numeric value (unquoted) */
    public const NUMBER = PowersOfTwo::_512K;

    /** Integer (no decimal part, no exponent, unquoted) */
    public const INT = PowersOfTwo::_1M;

    /** Strict unsigned integer (no decimal part, no exponent, unquoted, no prefix + or -) */
    public const UINT = PowersOfTwo::_2M;

    /** "0b0101" */
    public const BINARY_LITERAL = PowersOfTwo::_4M;

    /** "0xDEADBEEF" */
    public const HEXADECIMAL_LITERAL = PowersOfTwo::_8M;

    /** Formatted UUID like "12345678-90AB-CDEF-1234-567890ABCDEF" */
    public const UUID = PowersOfTwo::_16M;

    /** Any symbol (parenthesis, operators...) */
    public const SYMBOL = PowersOfTwo::_32M;

    /** Placeholder for variables in prepared statements */
    public const PLACEHOLDER = PowersOfTwo::_64M;

    /** Reserved word operator or token consisting of characters: !$%&*+-/:<=>?@\^|~ */
    public const OPERATOR = PowersOfTwo::_128M;

    /** Statement delimiter determined by DELIMITER keyword or default ";" */
    public const DELIMITER = PowersOfTwo::_256M;

    /** Token following the DELIMITER keyword and consisting of symbols */
    public const DELIMITER_DEFINITION = PowersOfTwo::_512M;

    /** Not a real token. Indicates expectation of end of token list */
    public const END = PowersOfTwo::_1G;

    /** Produced on invalid input to allow further parsing, instead of producing exception */
    public const INVALID = PowersOfTwo::_2G;

    /** Block of non-SQL code from MySQL tests */
    public const TEST_CODE = PowersOfTwo::_4G;

}
