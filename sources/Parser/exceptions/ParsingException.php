<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\Exception;
use Throwable;
use function debug_backtrace;

/**
 * ParsingException:
 *   - LexerException
 *     - EndOfCommentNotFoundException
 *     - EndOfStringNotFoundException
 *     - InvalidCharacterException
 *   - ParserException
 *     - InvalidVersionException
 *     - UnexpectedTokenException
 */
class ParsingException extends Exception
{

    /** @var bool */
    public static $debug = false;

    /** @var mixed[][]|null */
    public $backtrace;

    public function __construct(string $message, ?Throwable $previous = null, int $code = 0)
    {
        parent::__construct($message, $previous, $code);

        if (self::$debug) {
            $this->backtrace = debug_backtrace();
        }
    }

}
