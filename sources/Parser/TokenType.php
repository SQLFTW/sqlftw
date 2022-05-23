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

/**
 * Token type hierarchy:
 * ---------------------
 * - WHITESPACE
 * - COMMENT
 *    - BLOCK_COMMENT - "/* ... * /"
 *        ~ OPTIONAL_COMMENT - "/*! ... * /"
 *        - HINT_COMMENT - "/*+ ... * /"
 *    - DOUBLE_HYPHEN_COMMENT - "-- ..."
 *    - DOUBLE_SLASH_COMMENT - "// ..."
 *    - HASH_COMMENT - "# ..."
 * - KEYWORD
 *    - NAME + UNQUOTED_NAME
 *    - RESERVED
 *        - OPERATOR - "AND", "OR" etc.
 * - NAME
 *    - UNQUOTED_NAME - "table1"
 *    - DOUBLE_QUOTED_STRING - ""table1"" (standard, MySQL in ANSI_STRINGS mode)
 *    - BACKTICK_QUOTED_STRING - "`table1`" (MySQL, PostgreSQL, Sqlite)
 *    - SQUARE_BRACKETED_STRING - "[table1]" (MSSQL, SqLite)
 *    - AT_VARIABLE - "@var", "@@global", "@`192.168.0.1`" (also includes host names)
 *    - CHARSET_INTRODUCER - "_utf8'string'", "n'string'", "N'string'"
 * - VALUE
 *    - STRING
 *        - SINGLE_QUOTED_STRING - "'string'" (standard)
 *        - DOUBLE_QUOTED_STRING - ""string"" (MySQL in default mode)
 *        * DOLLAR_QUOTED_STRING - "$foo$table1$foo$" (PostgreSQL)
 *    - NUMBER
 *         - INT
 *             - UINT
 *         ~ FLOAT
 *         ~ DECIMAL
 *    - BINARY_LITERAL
 *    - HEXADECIMAL_LITERAL
 *    * DATE, TIME, DATETIME, TIMESTAMP - { d 'str' }, { t 'str' }, { ts 'str' }
 *    - UUID - e.g. "3E11FA47-71CA-11E1-9E33-C80AA9429562"
 *    ~ BOOLEAN (+- KEYWORD) - TRUE, FALSE, YES, NO, ON, OFF, 'T', 'F'
 *    ~ NULL (+ KEYWORD)
 *    ~ DEFAULT (+ KEYWORD)
 *    * OBJECT - OLD, NEW, VALUES
 *    - PLACEHOLDER - "?"
 * - SYMBOL - ()[]{}.,;
 *    - DELIMITER - default ";"
 *    - DELIMITER_DEFINITION
 *    - OPERATOR - everything else
 */
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

    /** Unquoted keyword recognized by given platform */
    public const KEYWORD = PowersOfTwo::_256;

    /** Unquoted reserved keyword recognized by given platform */
    public const RESERVED = PowersOfTwo::_512;

    /** Any name (quoted string or unquoted string other than a keyword) */
    public const NAME = PowersOfTwo::_1K;

    /** Any value (string, number, boolean...) */
    public const VALUE = PowersOfTwo::_2K;

    /** Any symbol (parenthesis, operators...) */
    public const SYMBOL = PowersOfTwo::_4K;

    /** Unquoted string consisting of letters, numbers and "_" */
    public const UNQUOTED_NAME = PowersOfTwo::_8K;

    /** Variable name starting with "@" */
    public const AT_VARIABLE = PowersOfTwo::_16K;

    /** Encoding definition starting with "_" and preceding a quoted string */
    public const STRING_INTRODUCER = PowersOfTwo::_32K;

    /** Quoted string value (not name) */
    public const STRING = PowersOfTwo::_64K;

    /** '...' - a string literal */
    public const SINGLE_QUOTED_STRING = PowersOfTwo::_128K;

    /** "..." - a name (standard) or a string literal (not standard, MySQL) */
    public const DOUBLE_QUOTED_STRING = PowersOfTwo::_256K;

    /** `...` - a name (not standard, MySQL, SqLite, PostgreSQL) */
    public const BACKTICK_QUOTED_STRING = PowersOfTwo::_512K;

    /** [...] - a name (not standard, MSSQL, SqLite) */
    public const SQUARE_BRACKETED_STRING = PowersOfTwo::_1M;

    /** Numeric value (unquoted) */
    public const NUMBER = PowersOfTwo::_2M;

    /** Integer (no decimal part, no exponent, unquoted) */
    public const INT = PowersOfTwo::_4M;

    /** Strict unsigned integer (no decimal part, no exponent, unquoted, no prefix + or -) */
    public const UINT = PowersOfTwo::_8M;

    /** "0b0101" */
    public const BINARY_LITERAL = PowersOfTwo::_16M;

    /** "0xDEADBEEF" */
    public const HEXADECIMAL_LITERAL = PowersOfTwo::_32M;

    /** Formatted UUID like "12345678-90AB-CDEF-1234-567890ABCDEF" */
    public const UUID = PowersOfTwo::_64M;

    /** Group/label separator */
    public const DOUBLE_COLON = PowersOfTwo::_128M;

    /** Placeholder for variables in prepared statements */
    public const PLACEHOLDER = PowersOfTwo::_256M;

    /** Reserved word operator or token consisting of characters: !$%&*+-/:<=>?@\^|~ */
    public const OPERATOR = PowersOfTwo::_512M;

    /** Statement delimiter determined by DELIMITER keyword or default ";" */
    public const DELIMITER = PowersOfTwo::_1G;

    /** Token following the DELIMITER keyword and consisting of symbols */
    public const DELIMITER_DEFINITION = PowersOfTwo::_2G;

    /** Not a real token. Indicates expectation of end of token list */
    public const END = PowersOfTwo::_4G;

    /** Produced on invalid input to allow further parsing, instead of producing exception */
    public const INVALID = PowersOfTwo::_8G;

    /** Block of non-SQL code from MySQL tests */
    public const TEST_CODE = PowersOfTwo::_16G;

}
