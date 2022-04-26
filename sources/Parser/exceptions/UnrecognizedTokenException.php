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

class UnrecognizedTokenException extends LexerException
{

    /** @var string */
    private $token;

    /** @var int */
    private $position;

    /** @var string */
    private $context;

    public function __construct(string $tokens, int $position, string $context, ?Throwable $previous = null)
    {
        parent::__construct("Unrecognized token \"$tokens\" at position $position in \"$context\".", $previous);

        $this->token = $tokens;
        $this->position = $position;
        $this->context = $context;
    }

    public function getToken(): string
    {
        return $this->token;
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
