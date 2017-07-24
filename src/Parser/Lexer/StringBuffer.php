<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Lexer;

class StringBuffer
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $string;

    /** @var int */
    public $position = 0;

    /** @var int */
    public $row = 1;

    /** @var int */
    public $column = 1;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    /**
     * Returns current character without changing position.
     * @param int $offset
     * @return string
     */
    public function get(int $offset = 0): string
    {
        return substr($this->string, $this->position + $offset, 1);
    }

    public function getRange(int $length, int $offset = 0): ?string
    {
        $result = substr($this->string, $this->position + $offset, $length);
        if (strlen($result) < $length) {
            return null;
        }
        return $result;
    }

}
