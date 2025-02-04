<?php

namespace SqlFtw\Error;

final class Severity
{

    /**
     * critical syntax error on lexer level
     */
    public const LEXER_ERROR = 10;

    /**
     * critical syntax error on parser level
     */
    public const PARSER_ERROR = 9;

    /**
     * static analysis error guaranteed to cause command failure (e.g. invalid assignment to system variable)
     *
     * Session cannot be updated with CRITICAL or above
     */
    public const CRITICAL = 8;

    /**
     * static analysis error not guaranteed to cause command failure (e.g. table not found, which might not be the case in runtime context)
     */
    public const ERROR = 7;

    /**
     * parser warning about not parsed features
     */
    public const PARSER_WARNING = 6;

    /**
     * static analysis warning about not analysed features
     */
    public const SKIP_WARNING = 5;

    /**
     * static analysis issue to consider
     */
    public const WARNING = 4;

    /**
     * static analysis tips for improvement
     */
    public const NOTICE = 3;

    /**
     * @var array<int, string>
     */
    public static array $labels = [
        self::LEXER_ERROR => 'Lexer error',
        self::PARSER_ERROR => 'Parser error',
        self::CRITICAL => 'Critical',
        self::ERROR => 'Error',
        self::PARSER_WARNING => 'Parser warning',
        self::SKIP_WARNING => 'Skip warning',
        self::WARNING => 'Warning',
        self::NOTICE => 'Notice',
    ];

}
