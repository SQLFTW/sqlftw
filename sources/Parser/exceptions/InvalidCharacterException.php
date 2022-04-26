<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Throwable;
use function ord;

class InvalidCharacterException extends LexerException
{

    /** @var string */
    private $char;

    /** @var int */
    private $position;

    /** @var string */
    private $context;

    public function __construct(string $char, int $position, string $context, ?Throwable $previous = null)
    {
        $ord = ord($char);

        parent::__construct("Invalid character of ASCII code $ord at position $position in \"$context\".", $previous);

        $this->char = $char;
        $this->position = $position;
        $this->context = $context;
    }

    public function getChar(): string
    {
        return $this->char;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getContext(): string
    {
        return $this->context;
    }

}