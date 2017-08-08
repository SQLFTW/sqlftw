<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Lexer;

use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\Mode;
use SqlFtw\Platform\Settings;

/**
 * todo:
 * - Date and Time Literals?
 * - Mysql string charset declaration (_utf* & N)
 * - PostgreSql dollar strings
 */
class Lexer
{
    use \Dogma\StrictBehaviorMixin;

    private const NUMBERS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const LETTERS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];

    private const OPERATOR_SYMBOLS = ['!', '%', '&', '*', '+', '-', '/', ':', '<', '=', '>', '\\', '^', '|', '~'];

    public const UUID_REGEXP = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}/i';

    /** @var int[] */
    private static $numbersKey;

    /** @var int[] */
    private static $hexadecKey;

    /** @var int[] */
    private static $nameCharsKey;

    /** @var int[] */
    private static $operatorSymbolsKey;

    /** @var \SqlFtw\Platform\Settings */
    private $settings;

    /** @var bool */
    private $withComments;

    /** @var bool */
    private $withWhitespace;

    /**
     * @param \SqlFtw\Platform\Settings $settings
     * @param bool $withComments
     * @param bool $withWhitespace
     */
    public function __construct(Settings $settings, bool $withComments = true, bool $withWhitespace = false)
    {
        self::$numbersKey = array_flip(self::NUMBERS);
        self::$hexadecKey = array_flip(array_merge(self::NUMBERS, ['A', 'a', 'B', 'b', 'C', 'c', 'D', 'd', 'E', 'e', 'F', 'f']));
        self::$nameCharsKey = array_flip(array_merge(self::LETTERS, self::NUMBERS, ['$', '_']));
        self::$operatorSymbolsKey = array_flip(self::OPERATOR_SYMBOLS);

        $this->settings = $settings;
        $this->withComments = $withComments;
        $this->withWhitespace = $withWhitespace;
    }

    /**
     * Tokenize SQL code. Expects line endings to be converted to "\n" and UTF-8 encoding.
     * @param string $string
     * @return \SqlFtw\Parser\Token[]
     */
    public function tokenizeAll(string $string): array
    {
        $tokens = [];
        foreach ($this->tokenize($string) as $token) {
            $tokens[] = $token;
        }

        return $tokens;
    }

    /**
     * Tokenize SQL code. Expects line endings to be converted to "\n" and UTF-8 encoding.
     * @param string $string
     * @return \SqlFtw\Parser\Token[]|\Generator
     */
    public function tokenize(string $string): \Generator
    {
        $string = new StringBuffer($string);

        $features = $this->settings->getPlatform()->getFeatures();
        $reservedKey = array_flip($features->getReservedWords());
        $keywordsKey = array_flip($features->getNonReservedWords());
        $operatorKeywordsKey = array_flip($features->getOperatorKeywords());

        $delimiter = $this->settings->getDelimiter();
        /** @var \SqlFtw\Parser\Token|null $previous */
        $previous = null;
        $condition = null;

        while (($char = $string->get()) !== '') {
            $uuidCheck = false;
            $position = $string->position;
            $string->position++;
            $string->column++;

            if ($char === $delimiter[0]) {
                do {
                    for ($n = 1; $n < strlen($delimiter); $n++) {
                        if ($string->get($n) !== $delimiter[$n]) {
                            break 2;
                        }
                    }
                    yield new Token(TokenType::SYMBOL | TokenType::DELIMITER, $position, $delimiter, null, $condition);
                    continue 2;
                } while (false);
            }

            switch ($char) {
                case ' ':
                case "\t":
                case "\r":
                case "\n":
                    $value = $char;
                    while (($next = $string->get()) !== '') {
                        if ($next === ' ' || $next === "\t" || $next === "\r") {
                            $value .= $next;
                            $string->position++;
                            $string->column++;
                        } elseif ($next === "\n") {
                            $value .= $next;
                            $string->position++;
                            $string->column = 1;
                            $string->row++;
                        } else {
                            break;
                        }
                    }
                    if ($this->withWhitespace) {
                        yield new Token(TokenType::WHITESPACE, $position, $value, null, $condition);
                    }
                    break;
                case '(':
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::LEFT_PARENTHESIS, $position, $char, null, $condition);
                    break;
                case ')':
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::RIGHT_PARENTHESIS, $position, $char, null, $condition);
                    break;
                case '[':
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::LEFT_SQUARE_BRACKET, $position, $char, null, $condition);
                    break;
                case ']':
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::RIGHT_SQUARE_BRACKET, $position, $char, null, $condition);
                    break;
                case '{':
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::LEFT_CURLY_BRACKET, $position, $char, null, $condition);
                    break;
                case '}':
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::RIGHT_CURLY_BRACKET, $position, $char, null, $condition);
                    break;
                case ',':
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::COMMA, $position, $char, null, $condition);
                    break;
                case ';':
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::SEMICOLON, $position, $char, null, $condition);
                    break;
                case ':':
                    $value = $char;
                    do {
                        $next = $string->get();
                        if (isset(self::$operatorSymbolsKey[$next])) {
                            $value .= $next;
                            $string->position++;
                            $string->column++;
                        } else {
                            break;
                        }
                    } while (true);
                    if ($value !== ':') {
                        yield $previous = new Token(TokenType::SYMBOL | TokenType::OPERATOR, $position, $value, null, $condition);
                    } else {
                        yield $previous = new Token(TokenType::SYMBOL | TokenType::DOUBLE_COLON, $position, $char, null, $condition);
                    }
                    break;
                case '*':
                    // /*!12345 ... */
                    if ($condition && $string->get() === '/') {
                        $condition = null;
                        $string->position++;
                        $string->column++;
                        break;
                    }
                    // continue
                case '!':
                case '%':
                case '&':
                case '<':
                case '=':
                case '>':
                case '\\':
                case '^':
                case '|':
                case '~':
                    $value = $char;
                    do {
                        $next = $string->get();
                        if (isset(self::$operatorSymbolsKey[$next])) {
                            $value .= $next;
                            $string->position++;
                            $string->column++;
                        } else {
                            break;
                        }
                    } while (true);
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::OPERATOR, $position, $char, null, $condition);
                    break;
                case '?':
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::PLACEHOLDER, $position, $char, null, $condition);
                    break;
                case '@':
                    if ($previous !== null && ($previous->type & TokenType::NAME)) {
                        // user @ host
                        yield $previous = new Token(TokenType::SYMBOL | TokenType::OPERATOR, $position, $char, null, $condition);
                        break;
                    }
                    // @variable
                    $value = $char;
                    while ($next = $string->get()) {
                        if ($next === '@' || isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                            $value .= $next;
                            $string->position++;
                            $string->column++;
                        } else {
                            break;
                        }
                    }
                    yield new Token(TokenType::NAME | TokenType::AT_VARIABLE, $position, $value, null, $condition);
                    break;
                case '#':
                    // # comment
                    $value = $char;
                    while (($next = $string->get()) !== '') {
                        if ($next === "\n") {
                            $value .= $next;
                            $string->position++;
                            $string->column = 1;
                            $string->row++;
                            break;
                        } else {
                            $value .= $next;
                            $string->position++;
                            $string->column++;
                        }
                    }
                    yield $previous = new Token(TokenType::COMMENT | TokenType::HASH_COMMENT, $position, $value, null, $condition);
                    break;
                case '/':
                    $next = $string->get();
                    if ($next === '/') {
                        // // comment
                        $string->position++;
                        $string->column++;
                        $value = $char . $next;
                        while (($next = $string->get()) !== '') {
                            if ($next === "\n") {
                                $value .= $next;
                                $string->position++;
                                $string->column = 1;
                                $string->row++;
                                break;
                            } else {
                                $value .= $next;
                                $string->position++;
                                $string->column++;
                            }
                        }
                        yield $previous = new Token(TokenType::COMMENT | TokenType::DOUBLE_SLASH_COMMENT, $position, $value, null, $condition);
                    } elseif ($next === '*') {
                        $string->position++;
                        $string->column++;
                        if ($condition !== null) {
                            /// fail
                        }
                        $column = $string->column;
                        $row = $string->row;

                        $value = $char . $next;
                        $ok = false;
                        do {
                            $next = $string->get();
                            if ($next === '') {
                                throw new \SqlFtw\Parser\Lexer\EndOfCommentNotFoundException(''); ///
                            } elseif ($next === '*' && $string->get(1) === '/') {
                                $value .= $next . $string->get(1);
                                $string->position += 2;
                                $string->column += 2;
                                $ok = true;
                                break;
                            } elseif ($next === "\n") {
                                $value .= $next;
                                $string->position++;
                                $string->column = 0;
                                $string->row++;
                            } else {
                                $value .= $next;
                                $string->position++;
                                $string->column++;
                            }
                        } while (true);
                        if (!$ok) {
                            throw new \SqlFtw\Parser\Lexer\EndOfCommentNotFoundException(''); ///
                        }

                        if ($value[2] === '!') {
                            // /*!12345 comment */
                            $versionId = (int) trim(substr($value, 2, 6));
                            if ($this->settings->getPlatform()->hasOptionalComments()
                                && ($versionId === 0 || $versionId <= $this->settings->getPlatform()->getVersion()->getId())
                            ) {
                                ///
                            } else {
                                yield new Token(TokenType::COMMENT | TokenType::BLOCK_COMMENT | TokenType::OPTIONAL_COMMENT, $position, $value);
                            }
                        } elseif ($value[2] === '+') {
                            // /*+ comment */
                            yield new Token(TokenType::COMMENT | TokenType::BLOCK_COMMENT | TokenType::HINT_COMMENT, $position, $value);
                        } else {
                            // /* comment */
                            yield new Token(TokenType::COMMENT | TokenType::BLOCK_COMMENT, $position, $value);
                        }
                    } else {
                        yield $previous = new Token(TokenType::SYMBOL | TokenType::OPERATOR, $position, $char, null, $condition);
                    }
                    break;
                case '"':
                    [$value, $orig] = $this->parseString($string, $char);
                    if ($this->settings->getMode()->contains(Mode::ANSI_QUOTES)) {
                        yield $previous = new Token(TokenType::NAME | TokenType::DOUBLE_QUOTED_STRING, $position, $value, $orig, $condition);
                    } else {
                        yield $previous = new Token(TokenType::VALUE | TokenType::STRING | TokenType::DOUBLE_QUOTED_STRING, $position, $value, $orig, $condition);
                    }
                    break;
                case '\'':
                    [$value, $orig] = $this->parseString($string, $char);
                    yield $previous = new Token(TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, $position, $value, $orig, $condition);
                    break;
                case '`':
                    [$value, $orig] = $this->parseString($string, $char);
                    yield $previous = new Token(TokenType::NAME | TokenType::BACKTICK_QUOTED_STRING, $position, $value, $orig, $condition);
                    break;
                case '.':
                    $next = $string->get();
                    if (isset(self::$numbersKey[$next])) {
                        [$value, $orig] = $this->parseNumber($string, '.');
                        if ($value !== null) {
                            yield $previous = new Token(TokenType::VALUE | TokenType::NUMBER, $position, $value, $orig, $condition);
                            break;
                        }
                    }
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::DOT, $position, $char, null, $condition);
                    break;
                case '-':
                    $next = $string->get();
                    if ($next === '-') {
                        $string->position++;
                        $string->column++;
                        $value = $char . $next;
                        while (($next = $string->get()) !== '') {
                            if ($next === "\n") {
                                $value .= $next;
                                $string->position++;
                                $string->column = 1;
                                $string->row++;
                                break;
                            } else {
                                $value .= $next;
                                $string->position++;
                                $string->column++;
                            }
                        }
                        yield $previous = new Token(TokenType::COMMENT | TokenType::DOUBLE_HYPHEN_COMMENT, $position, $value, null, $condition);
                        break;
                    }
                    if (isset(self::$numbersKey[$next])) {
                        [$value, $orig] = $this->parseNumber($string, '-');
                        if ($value !== null) {
                            yield $previous = new Token(TokenType::VALUE | TokenType::NUMBER, $position, $value, $orig, $condition);
                            break;
                        }
                    }
                    $value = $char;
                    while ($next = $string->get()) {
                        if (isset(self::$operatorSymbolsKey[$next])) {
                            $value .= $next;
                            $string->position++;
                            $string->column++;
                        } else {
                            break;
                        }
                    }
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::OPERATOR, $position, $value, null, $condition);
                    break;
                case '+':
                    $next = $string->get();
                    if (isset(self::$numbersKey[$next])) {
                        [$value, $orig] = $this->parseNumber($string, '+');
                        if ($value !== null) {
                            yield $previous = new Token(TokenType::VALUE | TokenType::NUMBER, $position, $value, $orig, $condition);
                            break;
                        }
                    }
                    $value = $char;
                    while ($next = $string->get()) {
                        if (isset(self::$operatorSymbolsKey[$next])) {
                            $value .= $next;
                            $string->position++;
                            $string->column++;
                        } else {
                            break;
                        }
                    }
                    yield $previous = new Token(TokenType::SYMBOL | TokenType::OPERATOR, $position, $value, null, $condition);
                    break;
                case '0':
                    $next = $string->get();
                    if ($next === 'b') {
                        $string->position++;
                        $string->column++;
                        $bits = '';
                        do {
                            $next = $string->get();
                            if ($next === '0' || $next === '1') {
                                $bits .= $next;
                                $string->position++;
                                $string->column++;
                            } else {
                                $orig = $char . 'b' . $bits;
                                yield $previous = new Token(TokenType::VALUE | TokenType::BINARY_LITERAL, $position, $bits, $orig, $condition);
                                break 2;
                            }
                        } while (true);
                    } elseif ($next === 'x') {
                        $string->position++;
                        $string->column++;
                        $bits = '';
                        do {
                            $next = $string->get();
                            if (isset(self::$hexadecKey[$next])) {
                                $bits .= $next;
                                $string->position++;
                                $string->column++;
                            } else {
                                $orig = $char . 'x' . $bits;
                                yield $previous = new Token(TokenType::VALUE | TokenType::HEXADECIMAL_LITERAL, $position, strtolower($bits), $orig, $condition);
                                break 2;
                            }
                        } while (true);
                    }
                    // continue
                case '1':
                case '2':
                case '3':
                case '4':
                case '5':
                case '6':
                case '7':
                case '8':
                case '9':
                    $uuidCheck = true;
                    $value = $string->getRange(36, -1);
                    // UUID
                    if ($value !== null && preg_match(self::UUID_REGEXP, $value)) {
                        $string->position += 35;
                        $string->column += 35;
                        yield $previous = new Token(TokenType::VALUE | TokenType::UUID, $position, $value, null, $condition);
                        break;
                    }
                    [$value, $orig] = $this->parseNumber($string, $char);
                    if ($value !== null) {
                        yield $previous = new Token(TokenType::VALUE | TokenType::NUMBER, $position, $value, $orig, $condition);
                        break;
                    }
                    // continue
                case 'B':
                case 'b':
                    // b'01'
                    // B'01'
                    if (($char === 'B' || $char === 'b') && $string->get() === '\'') {
                        $string->position++;
                        $string->column++;
                        $bits = '';
                        do {
                            $next = $string->get();
                            if ($next === '0' || $next === '1') {
                                $bits .= $next;
                                $string->position++;
                                $string->column++;
                            } elseif ($next === '\'') {
                                $string->position++;
                                $string->column++;
                                $orig = $char . '\'' . $bits . '\'';
                                yield $previous = new Token(TokenType::VALUE | TokenType::BINARY_LITERAL, $position, $bits, $orig, $condition);
                                break 2;
                            } else {
                                throw new \SqlFtw\Parser\Lexer\ExpectedTokenNotFoundException(''); ///
                            }
                        } while (true);
                    }
                    // continue
                case 'A':
                case 'a':
                case 'C':
                case 'c':
                case 'D':
                case 'd':
                case 'E':
                case 'e':
                case 'F':
                case 'f':
                    if (!$uuidCheck) {
                        $value = $string->getRange(36, -1);
                        // UUID
                        if ($value !== null && preg_match(self::UUID_REGEXP, $value)) {
                            $string->position += 35;
                            $string->column += 35;
                            yield $previous = new Token(TokenType::VALUE | TokenType::UUID, $position, $value, null, $condition);
                            break;
                        }
                    }
                    // continue
                case 'X':
                case 'x':
                    if (($char === 'X' || $char === 'x') && $string->get() === '\'') {
                        $string->position++;
                        $string->column++;
                        $bits = '';
                        do {
                            $next = $string->get();
                            if (isset(self::$hexadecKey[$next])) {
                                $bits .= $next;
                                $string->position++;
                                $string->column++;
                            } elseif ($next === '\'') {
                                $string->position++;
                                $string->column++;
                                $orig = $char . '\'' . $bits . '\'';
                                if ((strlen($bits) % 2) === 1) {
                                    throw new \SqlFtw\Parser\Lexer\ExpectedTokenNotFoundException(''); ///
                                }
                                yield $previous = new Token(TokenType::VALUE | TokenType::HEXADECIMAL_LITERAL, $position, strtolower($bits), $orig, $condition);
                                break 2;
                            } else {
                                throw new \SqlFtw\Parser\Lexer\ExpectedTokenNotFoundException(''); ///
                            }
                        } while (true);
                    }
                    // continue
                case 'G':
                case 'g':
                case 'H':
                case 'h':
                case 'I':
                case 'i':
                case 'J':
                case 'j':
                case 'K':
                case 'k':
                case 'L':
                case 'l':
                case 'M':
                case 'm':
                case 'N':
                case 'n':
                case 'O':
                case 'o':
                case 'P':
                case 'p':
                case 'Q':
                case 'q':
                case 'R':
                case 'r':
                case 'S':
                case 's':
                case 'T':
                case 't':
                case 'U':
                case 'u':
                case 'V':
                case 'v':
                case 'W':
                case 'w':
                case 'Y':
                case 'y':
                case 'Z':
                case 'z':
                case '$':
                    $value = $char;
                    while (($next = $string->get()) !== '') {
                        if (isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                            $value .= $next;
                            $string->position++;
                            $string->column++;
                        } else {
                            break;
                        }
                    }
                    $upper = strtoupper($value);
                    if ($upper === 'NULL') {
                        yield $previous = new Token(TokenType::KEYWORD | TokenType::VALUE, $position, 'NULL', $value, $condition);
                    } elseif ($upper === 'TRUE') {
                        yield $previous = new Token(TokenType::KEYWORD | TokenType::VALUE, $position, 'TRUE', $value, $condition);
                    } elseif ($upper === 'FALSE') {
                        yield $previous = new Token(TokenType::KEYWORD | TokenType::VALUE, $position, 'FALSE', $value, $condition);
                    } elseif (isset($reservedKey[$upper])) {
                        if (isset($operatorKeywordsKey[$upper])) {
                            yield $previous = new Token(TokenType::KEYWORD | TokenType::RESERVED | TokenType::OPERATOR, $position, $upper, $value, $condition);
                        } else {
                            yield $previous = new Token(TokenType::KEYWORD | TokenType::RESERVED, $position, $upper, $value, $condition);
                        }
                    } elseif (isset($keywordsKey[$upper])) {
                        yield $previous = new Token(TokenType::KEYWORD | TokenType::NAME | TokenType::UNQUOTED_NAME, $position, $upper, $value, $condition);
                    } elseif ($upper === 'DELIMITER' && $this->settings->getPlatform()->hasUserDelimiter()) {
                        yield new Token(TokenType::KEYWORD, $position, $upper, $value, $condition);
                        $position = $string->position;
                        $whitespace = $this->parseWhitespace($string);
                        if ($this->withWhitespace) {
                            yield new Token(TokenType::WHITESPACE, $position, $whitespace, null, $condition);
                        }
                        $position = $string->position;
                        $del = '';
                        while ($next = $string->get()) {
                            if ($next === ';' || isset(self::$operatorSymbolsKey[$next])) {
                                $del .= $next;
                                $string->position++;
                                $string->column++;
                            } else {
                                break;
                            }
                        }
                        if ($del === '') {
                            throw new \SqlFtw\Parser\Lexer\ExpectedTokenNotFoundException(''); ///
                        }
                        $delimiter = $del;
                        $this->settings->setDelimiter($delimiter);
                        yield $previous = new Token(TokenType::SYMBOL | TokenType::DELIMITER_DEFINITION, $position, $delimiter, $condition);
                    } else {
                        yield $previous = new Token(TokenType::NAME | TokenType::UNQUOTED_NAME, $position, $value, $value, $condition);
                    }
                    break;
                case '_':
                    /// charset declaration
                default:
                    if (ord($char) < 32) {
                        throw new \SqlFtw\Parser\Lexer\InvalidCharacterException($char, $position, ''); ///
                    }
                    $value = $char;
                    while (($next = $string->get()) !== '') {
                        if (isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                            $value .= $next;
                            $string->position++;
                            $string->column++;
                        } else {
                            break;
                        }
                    }
                    yield $previous = new Token(TokenType::NAME | TokenType::UNQUOTED_NAME, $position, $value, $value, $condition);
            }
        }
    }

    private function parseWhitespace(StringBuffer $string)
    {
        $whitespace = '';
        while (($next = $string->get()) !== '') {
            if ($next === ' ' || $next === "\t" || $next === "\r") {
                $whitespace .= $next;
                $string->position++;
                $string->column++;
            } elseif ($next === "\n") {
                $whitespace .= $next;
                $string->position++;
                $string->column = 1;
                $string->row++;
            } else {
                break;
            }
        }

        return $whitespace;
    }

    /**
     * @param \SqlFtw\Parser\Lexer\StringBuffer $string
     * @param string $quote
     * @return string[] ($value, $orig)
     */
    private function parseString(StringBuffer $string, string $quote): array
    {
        $backslashes = !$this->settings->getMode()->contains(Mode::NO_BACKSLASH_ESCAPES);

        $orig[] = $quote;
        $escaped = false;
        $finished = false;
        while ($next = $string->get()) {
            if ($next === $quote) {
                $orig[] = $next;
                $string->position++;
                $string->column++;
                if ($escaped) {
                    $escaped = false;
                } elseif ($string->get() === $quote) {
                    $escaped = true;
                } else {
                    $finished = true;
                    break;
                }
            } elseif ($next === "\n") {
                $orig[] = $next;
                $string->position++;
                $string->column = 1;
                $string->row++;
            } elseif ($backslashes && $next === '\\') {
                $escaped = !$escaped;
                $orig[] = $next;
                $string->position++;
                $string->column++;
            } else {
                $orig[] = $next;
                $string->position++;
                $string->column++;
            }
        }
        if (!$finished) {
            throw new \SqlFtw\Parser\Lexer\EndOfStringNotFoundException(''); ///
        }
        $orig = implode('', $orig);
        $value = $this->unescapeString($orig, $quote);

        return [$value, $orig];
    }

    /**
     * NO_BACKSLASH_ESCAPES mode:
     * Disable the use of the backslash character (\) as an escape character within strings.
     * With this mode enabled, backslash becomes an ordinary character like any other.
     *
     * \0	An ASCII NUL (X'00') character
     * \'	A single quote (') character
     * \"	A double quote (") character
     * \b	A backspace character
     * \n	A newline (linefeed) character
     * \r	A carriage return character
     * \t	A tab character
     * \Z	ASCII 26 (Control+Z)
     * \\	A backslash (\) character
     *
     * (do not unescape. keep original for LIKE)
     * \%	A % character
     * \_	A _ character
     *
     * A ' inside a string quoted with ' may be written as ''.
     * A " inside a string quoted with " may be written as "".
     */
    private function unescapeString(string $string, string $quote): string
    {
        $translations = [
            '\\0' => "\x00",
            '\\\'' => '\'',
            '\\""' => '""',
            '\\b' => "\x08",
            '\\n' => "\n",
            '\\r' => "\r",
            '\\t' => "\t",
            '\\Z' => "\x1A",
            '\\\\' => '\\',
        ];

        $string = substr($string, 1, -1);

        $string = str_replace($quote . $quote, $quote, $string);
        if (!$this->settings->getMode()->contains(Mode::NO_BACKSLASH_ESCAPES)) {
            $string = str_replace(array_keys($translations), array_values($translations), $string);

            ///
        }

        return $string;
    }

    /**
     * @param \SqlFtw\Parser\Lexer\StringBuffer $string
     * @param string $start
     * @return int[]|float[]|string[]|null[] (int|float|string|null $value, string|null $orig)
     */
    private function parseNumber(StringBuffer $string, string $start): array
    {
        $offset = 0;
        $num = isset(self::$numbersKey[$start]);
        $base = $start;
        do {
            // integer
            do {
                $next = $string->get($offset);
                if (isset(self::$numbersKey[$next])) {
                    $base .= $next;
                    $offset++;
                    $num = true;
                } else {
                    break;
                }
            } while (true);

            // decimal part
            if ($next === '.') {
                if ($start !== '.') {
                    $base .= $next;
                    $offset++;
                    do {
                        $next = $string->get($offset);
                        if (isset(self::$numbersKey[$next])) {
                            $base .= $next;
                            $offset++;
                            $num = true;
                        } else {
                            break;
                        }
                    } while (true);
                } else {
                    break;
                }
            }

            // exponent
            $next = $string->get($offset);
            $exp = '';
            do {
                if ($next === 'e' || $next === 'E') {
                    $exp = $next;
                    $offset++;
                    $next = $string->get($offset);
                    if ($next === '+' || $next === '-' || isset(self::$numbersKey[$next])) {
                        $exp .= $next;
                        $offset++;
                    }
                    do {
                        $next = $string->get($offset);
                        if (isset(self::$numbersKey[$next])) {
                            $exp .= $next;
                            $offset++;
                        } else {
                            if (strlen(trim($exp, 'e+-')) < 1 && strpos($base, '.') !== false) {
                                throw new \SqlFtw\Parser\Lexer\ExpectedTokenNotFoundException(''); //
                            }
                            break;
                        }
                    } while (true);
                } elseif (isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                    $num = false;
                    break 2;
                }
            } while (false);
        } while (false);

        if (!$num) {
            return [null, null];
        }

        $orig = $base;
        $value = strtolower(rtrim(ltrim($base, '+'), '.'));
        if ($value[0] === '.') {
            $value = '0' . $value;
        }
        if ($exp !== '') {
            $orig .= $exp;
            $value = (string) (float) ($value . $exp);
        }
        if ($value === (string) (int) $value) {
            $value = (int) $value;
        } elseif ($value === (string) (float) $value) {
            $value = (float) $value;
        }

        $len = strlen($orig) - 1;
        $string->position += $len;
        $string->column += $len;

        return [$value, $orig];
    }

}
