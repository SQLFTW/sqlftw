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

    /** @var int[] (int $row => int $startPosition) */
    private $rowStarts = [1 => 0];

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    /**
     * Returns next character and advances position to its position.
     * @return string
     */
    public function next(): string
    {
        $char = substr($this->string, $this->position, 1);
        $this->consume($char);

        return $char;
    }

    public function skip(int $count): void
    {
        $this->consume(str_repeat(' ', $count));
    }

    public function consume(string $string): void
    {
        for ($n = 0; $n < strlen($string); $n++) {
            $char = substr($string, $n, 1);
            $this->position++;
            if ($char === "\n") {
                $this->row++;
                $this->rowStarts[$this->row] = $this->position;
                $this->column = 0;
            } else {
                $this->column++;
            }
        }
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

    /**
     * Checks if given string follows, starting from current position.
     * @param string $string
     * @return bool
     */
    public function follows(string $string): bool
    {
        return strpos($this->string, $string, $this->position) === $this->position;
    }

    /**
     * Checks if any of given characters follows.
     * @param string[] $chars
     * @param int $offset
     * @return bool
     */
    public function followsAny(array $chars, int $offset = 0): bool
    {
        return in_array($this->get($offset), $chars);
    }

    /**
     * When given character follows, return the character and advance pointer to its position.
     * @param string $string
     * @return string|null
     */
    public function mayConsume(string $string): ?string
    {
        if ($this->follows($string)) {
            $this->consume($string);
            return $string;
        } else {
            return null;
        }
    }

    /**
     * When any of given characters follows, return the character and advance pointer to its position.
     * @param array $chars
     * @return null|string
     */
    public function mayConsumeAny(array $chars): ?string
    {
        try {
            return $this->consumeAny($chars);
        } catch (\SqlFtw\Parser\Lexer\ExpectedTokenNotFoundException $e) {
            return null;
        }
    }

    /**
     * Returns string before given character. Advances position to last taken character.
     * @param string $char
     * @param bool $include
     * @return string|null
     */
    public function consumeTillNext(string $char, bool $include = false): ?string
    {
        $endPosition = strpos($this->string, $char, $this->position);
        if ($endPosition === false) {
            throw new \SqlFtw\Parser\Lexer\ExpectedTokenNotFoundException('');
        }
        $string = substr($this->string, $this->position, $endPosition - $this->position + ($include ? strlen($char) : 0));
        if ($string === '') {
            throw new \SqlFtw\Parser\Lexer\ExpectedTokenNotFoundException('');
        }
        $this->consume($string);

        return $string;
    }

    /**
     * Returns string before end of string or given character. Advances position to last taken character.
     * @param string $char
     * @return string
     */
    public function consumeTillEofOrNext(string $char): string
    {
        dump($this->string);
        dump($this->position);
        dump($char);
        $endPosition = strpos($this->string, $char, $this->position);
        dump($endPosition);
        $string = substr($this->string, $this->position, $endPosition !== false ? $endPosition - $this->position : null);
        if ($string === '') {
            throw new \SqlFtw\Parser\Lexer\ExpectedTokenNotFoundException('');
        }
        $this->consume($string);

        return $string;
    }

    /**
     * Returns string before first given unescaped character. Advances position to last taken character.
     * @param string $endChar
     * @param string $escapeChar
     * @param bool $escapeByDoubling
     * @return string
     */
    public function consumeTillNextNonEscaped(string $endChar, string $escapeChar, bool $escapeByDoubling = false): string
    {
        $string = '';
        do {
            $block = $this->consumeTillNext($endChar);
            if ($block === null) {
                throw new \SqlFtw\Parser\Lexer\EndOfStringNotFoundException('');
            }
            $string .= $block;

            $precedingEscapeCharsCount = strlen($block) - strlen(rtrim($block, $escapeChar));
            if ($escapeByDoubling) {
                $followingEndChars = $this->mayConsumeAny([$endChar]);
                $followingEndCharsCount = strlen($followingEndChars);
                $string .= $followingEndChars;
            } else {
                $followingEndCharsCount = 0;
            }

            if ((($precedingEscapeCharsCount + $followingEndCharsCount) % 2) === 0) {
                break;
            }
        } while (true);

        if ($string === '') {
            throw new \SqlFtw\Parser\Lexer\ExpectedTokenNotFoundException('');
        }

        return $string;
    }

    /**
     * Returns string consisting of any of given characters. Advances position to last taken character.
     * @param string[] $chars
     * @return string
     */
    public function consumeAny(array $chars): string
    {
        $n = 0;
        $string = '';
        $char = substr($this->string, $this->position + $n, 1);
        while (in_array($char, $chars)) {
            $string .= $char;
            $n++;
            $char = substr($this->string, $this->position + $n, 1);
        }
        if ($string === '') {
            throw new \SqlFtw\Parser\Lexer\ExpectedTokenNotFoundException('');
        }
        $this->consume($string);

        return $string;
    }

    /**
     * Returns string consisting of any of matching characters. Advances position to last taken character.
     * @param string $pattern
     * @return string
     */
    public function consumeMatching(string $pattern): string
    {
        $n = 0;
        $string = '';
        $char = substr($this->string, $this->position + $n, 1);
        while ($char && preg_match($pattern, $char)) {
            $string .= $char;
            $n++;
            $char = substr($this->string, $this->position + $n, 1);
        }
        if ($string === '') {
            throw new \SqlFtw\Parser\Lexer\ExpectedTokenNotFoundException('');
        }
        $this->consume($string);

        return $string;
    }

    public function tryMatch(string $pattern): ?string
    {
        $string = substr($this->string, $this->position, strlen($pattern));

        if (preg_match($pattern, $string, $match)) {
            return $match[0];
        } else {
            return null;
        }
    }

    public function getContext(): string
    {
        return substr($this->string, $this->position - 25, $this->position + 25);
    }

    public function getCurrentRow(): string
    {
        $currentRowStart = $this->rowStarts[$this->row];
        return substr($this->string, $currentRowStart, strpos($this->string, "\n", $currentRowStart) - $currentRowStart);
    }

}
