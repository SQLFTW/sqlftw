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

    // primary types ---------------------------------------------------------------------------------------------------

    /** Space, \t, \r, \n */
    public const WHITESPACE = PowersOfTwo::_1K;

    /** -- ... etc. (standard) */
    public const LINE_COMMENT = PowersOfTwo::_2K;

    /** /* ... * / (standard) */
    public const BLOCK_COMMENT = PowersOfTwo::_4K;

    /** Unquoted string consisting of letters, numbers and "_" */
    public const UNQUOTED_NAME = PowersOfTwo::_32K;

    /** Unquoted keyword recognized by given platform */
    public const KEYWORD = PowersOfTwo::_16K;

    /** Unquoted reserved keyword recognized by given platform */
    public const RESERVED = PowersOfTwo::_8K;

    /** Quoted string consisting of letters, numbers and "_" */
    public const QUOTED_NAME = PowersOfTwo::_64K;

    /** Variable name starting with "@" */
    public const AT_VARIABLE = PowersOfTwo::_128K;

    /** Numeric value (unquoted) */
    public const NUMBER = PowersOfTwo::_1M;

    /** Integer (no decimal part, no exponent, unquoted) */
    public const INT = PowersOfTwo::_512K;

    /** Strict unsigned integer (no decimal part, no exponent, unquoted, no prefix + or -) */
    public const UINT = PowersOfTwo::_256K;

    /** Quoted string value (not name) */
    public const STRING = PowersOfTwo::_2M;

    /** Binary or hexadecimal literal */
    public const BIT_STRING = PowersOfTwo::_4M;

    /** Formatted UUID like "12345678-90AB-CDEF-1234-567890ABCDEF" */
    public const UUID = PowersOfTwo::_8M;

    /** Any symbol (parenthesis, operators...) */
    public const SYMBOL = PowersOfTwo::_16M;

    /** Reserved word operator or token consisting of characters: !$%&*+-/:<=>?@\^|~ */
    public const OPERATOR = PowersOfTwo::_256;

    /** Any placeholder */
    public const PLACEHOLDER = PowersOfTwo::_32M;

    /** Statement delimiter determined by DELIMITER keyword or default ";" */
    public const DELIMITER = PowersOfTwo::_64M;

    /** Token following the DELIMITER keyword */
    public const DELIMITER_DEFINITION = PowersOfTwo::_128M;

    /** Not a real token. Indicates expectation of end of token list */
    public const END = PowersOfTwo::_256M;

    /** Produced on invalid input to allow further parsing, instead of producing exception */
    public const INVALID = PowersOfTwo::_1G;

    // groups ----------------------------------------------------------------------------------------------------------

    /** Any comment */
    public const COMMENTS = self::BLOCK_COMMENT | self::LINE_COMMENT;

    /** Any name (quoted string or unquoted string other than a keyword) */
    public const NAMES = self::UNQUOTED_NAME | self::QUOTED_NAME | self::AT_VARIABLE;

    /** Any value (string, number, boolean...) */
    public const VALUES = self::NUMBER | self::STRING | self::BIT_STRING | self::UUID;

    // flags -----------------------------------------------------------------------------------------------------------

    public const SINGLE_QUOTED = 1;

    public const DOUBLE_QUOTED = 2;

    public const BACKTICK_QUOTED = 4;

    public const BRACKETED = 8;


    public const BINARY = 4;

    public const OCTAL = 8;

    public const HEXADECIMAL = 16;

    // composites ------------------------------------------------------------------------------------------------------

    /** -- ... (standard) */
    public const DOUBLE_HYPHEN_COMMENT = self::LINE_COMMENT | 1;

    /** // ... (not standard) */
    public const DOUBLE_SLASH_COMMENT = self::LINE_COMMENT | 2;

    /** # ... (not standard) */
    public const HASH_COMMENT = self::LINE_COMMENT | 4;

    /** /*!50701 ... * / (MySQL) */
    public const OPTIONAL_COMMENT = self::BLOCK_COMMENT | 8;

    /** /*+ ... * / (MySQL) */
    public const OPTIMIZER_HINT_COMMENT = self::BLOCK_COMMENT | 16;


    /** "..." - a name (standard) */
    public const DOUBLE_QUOTED_NAME = self::QUOTED_NAME | self::DOUBLE_QUOTED;

    /** `...` - a name (not standard, MySQL, SqLite, PostgreSQL) */
    public const BACKTICK_QUOTED_NAME = self::QUOTED_NAME | self::BACKTICK_QUOTED;

    /** [...] - a name (not standard, MSSQL, SqLite) */
    public const BRACKETED_NAME = self::QUOTED_NAME | self::BRACKETED;


    /** '...' - a string literal */
    public const SINGLE_QUOTED_STRING = self::STRING | self::SINGLE_QUOTED;

    /** "..." - a string literal (not standard, MySQL) */
    public const DOUBLE_QUOTED_STRING = self::STRING | self::DOUBLE_QUOTED;

    /** "0b0101" */
    public const BINARY_LITERAL = self::BINARY | self::BIT_STRING;

    /** "0o127" */
    public const OCTAL_LITERAL = self::OCTAL | self::BIT_STRING;

    /** "0xDEADBEEF" */
    public const HEXADECIMAL_LITERAL = self::HEXADECIMAL | self::BIT_STRING;


    /** Placeholder for variables in prepared statements "?" */
    public const QUESTION_MARK_PLACEHOLDER = self::PLACEHOLDER | 1;

    /** Placeholder used in client-side code in Doctrine, Laravel etc. "?123" */
    public const NUMBERED_QUESTION_MARK_PLACEHOLDER = self::PLACEHOLDER | 2;

    /** Placeholder used in client-side code in Doctrine, Laravel etc. ":variable" */
    public const DOUBLE_COLON_PLACEHOLDER = self::PLACEHOLDER | 4;

}
