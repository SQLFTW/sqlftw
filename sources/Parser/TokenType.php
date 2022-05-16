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
 *        - SINGLE_QUOTED_STRING "'string'" (standard)
 *        - DOUBLE_QUOTED_STRING ""string"" (MySQL in default mode)
 *        * DOLLAR_QUOTED_STRING - "$foo$table1$foo$" (PostgreSQL)
 *    - NUMBER
 *         ~ INTEGER
 *         ~ FLOAT
 *         ~ DECIMAL
 *    - BINARY_LITERAL
 *    - HEXADECIMAL_LITERAL
 *    * DATE, TIME, DATETIME, TIMESTAMP - { d 'str' }, { t 'str' }, { ts 'str' }
 *    - UUID "3E11FA47-71CA-11E1-9E33-C80AA9429562"
 *    ~ BOOLEAN (+- KEYWORD) - TRUE, FALSE, YES, NO, ON, OFF, 'T', 'F'
 *    ~ NULL (+ KEYWORD)
 *    ~ DEFAULT (+ KEYWORD)
 *    * OBJECT - OLD, NEW, VALUES
 *    - PLACEHOLDER - "?"
 * - SYMBOL ()[]{}.,;
 *    - LEFT_PARENTHESIS, RIGHT_PARENTHESIS, LEFT_SQUARE_BRACKET, RIGHT_SQUARE_BRACKET, LEFT_CURLY_BRACKET, RIGHT_CURLY_BRACKET
 *    - DOT, SEMICOLON
 *    - DELIMITER - default ";"
 *    - DELIMITER_DEFINITION
 *    - OPERATOR - everything else
 */
class TokenType extends IntSet
{

    /** Space, \t, \r, \n */
    public const WHITESPACE = 0x1;

    /** Any comment */
    public const COMMENT = 0x2;

    /** /* ... * / (standard) */
    public const BLOCK_COMMENT = 0x4;

    /** /*!50701 ... * / (MySQL) */
    public const OPTIONAL_COMMENT = 0x8;

    /** /*+ ... * / (MySQL) */
    public const HINT_COMMENT = 0x10;

    /** -- ... (standard) */
    public const DOUBLE_HYPHEN_COMMENT = 0x20;

    /** // ... (not standard) */
    public const DOUBLE_SLASH_COMMENT = 0x40;

    /** # ... (not standard) */
    public const HASH_COMMENT = 0x80;

    /** Unquoted keyword recognized by given platform */
    public const KEYWORD = 0x100;

    /** Unquoted reserved keyword recognized by given platform */
    public const RESERVED = 0x200;

    /** Any name (quoted string or unquoted string other than a keyword) */
    public const NAME = 0x400;

    /** Any value (string, number, boolean...) */
    public const VALUE = 0x800;

    /** Any symbol (parenthesis, operators...) */
    public const SYMBOL = 0x1000;

    /** Unquoted string consisting of letters, numbers and "_" */
    public const UNQUOTED_NAME = 0x2000;

    /** Variable name starting with "@" */
    public const AT_VARIABLE = 0x4000;

    /** Encoding definition starting with "_" and preceding a quoted string */
    public const CHARSET_INTRODUCER = 0x8000;

    /** Quoted string value (not name) */
    public const STRING = 0x10000;

    /** '...' - a string literal */
    public const SINGLE_QUOTED_STRING = 0x20000;

    /** "..." - a name (standard) or a string literal (not standard, MySQL) */
    public const DOUBLE_QUOTED_STRING = 0x40000;

    /** `...` - a name (not standard, MySQL, SqLite, PostgreSQL) */
    public const BACKTICK_QUOTED_STRING = 0x80000;

    /** [...] - a name (not standard, MSSQL, SqLite) */
    public const SQUARE_BRACKETED_STRING = 0x100000;

    /** Numeric value (unquoted) */
    public const NUMBER = 0x200000;

    /** Formatted UUID like "12345678-90AB-CDEF-1234-567890ABCDEF" */
    public const UUID = 0x400000;

    /** "0b0101" */
    public const BINARY_LITERAL = 0x800000;

    /** "0xDEADBEEF" */
    public const HEXADECIMAL_LITERAL = 0x1000000;

    /** "(" */
    public const LEFT_PARENTHESIS = 0x2000000;

    /** ")" */
    public const RIGHT_PARENTHESIS = 0x4000000;

    /** "[" */
    public const LEFT_SQUARE_BRACKET = 0x8000000;

    /** "]" */
    public const RIGHT_SQUARE_BRACKET = 0x10000000;

    /** "{" */
    public const LEFT_CURLY_BRACKET = 0x20000000;

    /** "}" */
    public const RIGHT_CURLY_BRACKET = 0x40000000;

    /** Name separator */
    public const DOT = 0x80000000;

    /** Group/label separator */
    public const DOUBLE_COLON = 0x200000000;

    /** Semicolon when not used as a statement delimiter - expression separator in PLSQL */
    public const SEMICOLON = 0x400000000;

    /** Placeholder for variables in prepared statements */
    public const PLACEHOLDER = 0x800000000;

    /** Reserved word operator or token consisting of characters: !$%&*+-/:<=>?@\^|~ */
    public const OPERATOR = 0x1000000000;

    /** Statement delimiter determined by DELIMITER keyword or default ";" */
    public const DELIMITER = 0x2000000000;

    /** Token following the DELIMITER keyword and consisting of symbols */
    public const DELIMITER_DEFINITION = 0x4000000000;

    /** Not a real token. Indicates expectation of end of token list */
    public const END = 0x8000000000;

    /** Produced on invalid input to allow further parsing, instead of producing exception */
    public const INVALID = 0x10000000000;

    /** Block of Perl code from MySQL tests */
    public const TEST_CODE = 0x20000000000;

}
