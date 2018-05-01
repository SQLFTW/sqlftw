<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Lexer;

class InvalidCharacterException extends LexerException
{

    /** @var string */
    private $char;

    /** @var int */
    private $position;

    /** @var string */
    private $context;

    public function __construct(string $char, int $position, string $context, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Invalid character of ASCII code %d at position %d in "%s".', ord($char), $position, $context), $previous);

        $this->char = $char;
        $this->position = $position;
        $this->context = $context;
    }

}
