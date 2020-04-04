<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable Squiz.Arrays.ArrayDeclaration.ValueNoNewline
// phpcs:disable SlevomatCodingStandard.ControlStructures.JumpStatementsSpacing

namespace SqlFtw\Parser\Lexer;

use Dogma\StrictBehaviorMixin;
use Generator;
use SqlFtw\Parser\ParserException;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenType as T;
use SqlFtw\Platform\Mode;
use SqlFtw\Platform\PlatformSettings;
use function array_flip;
use function array_keys;
use function array_merge;
use function array_values;
use function implode;
use function ltrim;
use function ord;
use function preg_match;
use function rtrim;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use function trim;

/**
 * todo:
 * - Date and Time Literals?
 * - Mysql string charset declaration (_utf* & N)
 * - \N is synonym for NULL (until 8.0)
 */
class Lexer
{
    use StrictBehaviorMixin;

    private const NUMBERS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const LETTERS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];

    private const OPERATOR_SYMBOLS = ['!', '%', '&', '*', '+', '-', '/', ':', '<', '=', '>', '\\', '^', '|', '~'];

    public const UUID_REGEXP = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i';

    /** @var int[] */
    private static $numbersKey;

    /** @var int[] */
    private static $hexadecKey;

    /** @var int[] */
    private static $nameCharsKey;

    /** @var int[] */
    private static $operatorSymbolsKey;

    /** @var PlatformSettings */
    private $settings;

    /** @var bool */
    private $withComments;

    /** @var bool */
    private $withWhitespace;

    public function __construct(
        PlatformSettings $settings,
        bool $withComments = true,
        bool $withWhitespace = false
    ) {
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
     * @return Token[]
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
     * @return Token[]|Generator
     */
    public function tokenize(string $string): Generator
    {
        $length = strlen($string);
        $position = 0;
        $row = 1;
        $column = 1;

        $features = $this->settings->getPlatform()->getFeatures();
        $reservedKey = array_flip($features->getReservedWords());
        $keywordsKey = array_flip($features->getNonReservedWords());
        $operatorKeywordsKey = array_flip($features->getOperatorKeywords());

        $delimiter = $this->settings->getDelimiter();
        /** @var Token|null $previous */
        $previous = null;
        $condition = null;

        while ($position < $length) {
            $uuidCheck = false;
            $char = $string[$position];
            $start = $position;
            $position++;
            $column++;

            if ($char === $delimiter[0]) {
                do {
                    $delimiterLength = strlen($delimiter);
                    for ($n = 1; $n < $delimiterLength; $n++) {
                        if ($position + $n >= $length || $string[$position + $n] !== $delimiter[$n]) {
                            break 2;
                        }
                    }
                    yield new Token(T::SYMBOL | T::DELIMITER, $start, $delimiter, null, $condition);
                    continue 2;
                } while (false);
            }

            switch ($char) {
                case ' ':
                case "\t":
                case "\r":
                case "\n":
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if ($next === ' ' || $next === "\t" || $next === "\r") {
                            $value .= $next;
                            $position++;
                            $column++;
                        } elseif ($next === "\n") {
                            $value .= $next;
                            $position++;
                            $column = 1;
                            $row++;
                        } else {
                            break;
                        }
                    }
                    if ($this->withWhitespace) {
                        yield new Token(T::WHITESPACE, $start, $value, null, $condition);
                    }
                    break;
                case '(':
                    yield $previous = new Token(T::SYMBOL | T::LEFT_PARENTHESIS, $start, $char, null, $condition);
                    break;
                case ')':
                    yield $previous = new Token(T::SYMBOL | T::RIGHT_PARENTHESIS, $start, $char, null, $condition);
                    break;
                case '[':
                    yield $previous = new Token(T::SYMBOL | T::LEFT_SQUARE_BRACKET, $start, $char, null, $condition);
                    break;
                case ']':
                    yield $previous = new Token(T::SYMBOL | T::RIGHT_SQUARE_BRACKET, $start, $char, null, $condition);
                    break;
                case '{':
                    yield $previous = new Token(T::SYMBOL | T::LEFT_CURLY_BRACKET, $start, $char, null, $condition);
                    break;
                case '}':
                    yield $previous = new Token(T::SYMBOL | T::RIGHT_CURLY_BRACKET, $start, $char, null, $condition);
                    break;
                case ',':
                    yield $previous = new Token(T::SYMBOL | T::COMMA, $start, $char, null, $condition);
                    break;
                case ';':
                    yield $previous = new Token(T::SYMBOL | T::SEMICOLON, $start, $char, null, $condition);
                    break;
                case ':':
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (isset(self::$operatorSymbolsKey[$next])) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    if ($value !== ':') {
                        yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $value, null, $condition);
                    } else {
                        yield $previous = new Token(T::SYMBOL | T::DOUBLE_COLON, $start, $char, null, $condition);
                    }
                    break;
                case '*':
                    // /*!12345 ... */
                    if ($position < $length && $condition && $string[$position] === '/') {
                        $condition = null;
                        $position++;
                        $column++;
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
                    while ($position < $length) {
                        $next = $string[$position];
                        if (isset(self::$operatorSymbolsKey[$next])) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $value, null, $condition);
                    break;
                case '?':
                    yield $previous = new Token(T::SYMBOL | T::PLACEHOLDER, $start, $char, null, $condition);
                    break;
                case '@':
                    if ($previous !== null && ($previous->type & (T::STRING | T::NAME))) {
                        // user @ host
                        yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $char, null, $condition);
                        break;
                    }
                    // @variable
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if ($next === '@' || isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    yield new Token(T::NAME | T::AT_VARIABLE, $start, $value, null, $condition);
                    break;
                case '#':
                    // # comment
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if ($next === "\n") {
                            $value .= $next;
                            $position++;
                            $column = 1;
                            $row++;
                            break;
                        } else {
                            $value .= $next;
                            $position++;
                            $column++;
                        }
                    }
                    if ($this->withComments) {
                        yield $previous = new Token(T::COMMENT | T::HASH_COMMENT, $start, $value, null, $condition);
                    }
                    break;
                case '/':
                    $next = $position < $length ? $string[$position] : '';
                    if ($next === '/') {
                        // // comment
                        $position++;
                        $column++;
                        $value = $char . $next;
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === "\n") {
                                $value .= $next;
                                $position++;
                                $column = 1;
                                $row++;
                                break;
                            } else {
                                $value .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        if ($this->withComments) {
                            yield $previous = new Token(T::COMMENT | T::DOUBLE_SLASH_COMMENT, $start, $value, null, $condition);
                        }
                    } elseif ($next === '*') {
                        $position++;
                        $column++;
                        if ($condition !== null) {
                            throw new ParserException('Comment inside conditional comment');
                        }
                        //$column = $string->column;
                        //$row = $string->row;

                        $value = $char . $next;
                        $ok = false;
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === '*' && ($position + 1 < $length) && $string[$position + 1] === '/') {
                                $value .= $next . $string[$position + 1];
                                $position += 2;
                                $column += 2;
                                $ok = true;
                                break;
                            } elseif ($next === "\n") {
                                $value .= $next;
                                $position++;
                                $column = 0;
                                $row++;
                            } else {
                                $value .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        if (!$ok) {
                            throw new EndOfCommentNotFoundException(''); // todo
                        }

                        if ($value[2] === '!') {
                            // /*!12345 comment */
                            $versionId = (int) trim(substr($value, 2, 6));
                            if ($this->settings->getPlatform()->hasOptionalComments()
                                && ($versionId === 0 || $versionId <= $this->settings->getPlatform()->getVersion()->getId())
                            ) {
                                // todo: conditional comments
                                $condition = 'todo';
                            } else {
                                yield new Token(T::COMMENT | T::BLOCK_COMMENT | T::OPTIONAL_COMMENT, $start, $value);
                            }
                        } elseif ($value[2] === '+') {
                            // /*+ comment */
                            yield new Token(T::COMMENT | T::BLOCK_COMMENT | T::HINT_COMMENT, $start, $value);
                        } else {
                            // /* comment */
                            if ($this->withComments) {
                                yield new Token(T::COMMENT | T::BLOCK_COMMENT, $start, $value);
                            }
                        }
                    } else {
                        yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $char, null, $condition);
                    }
                    break;
                case '"':
                    [$value, $orig] = $this->parseString($string, $position, $column, $row, $char);
                    if ($this->settings->getMode()->contains(Mode::ANSI_QUOTES)) {
                        yield $previous = new Token(T::NAME | T::DOUBLE_QUOTED_STRING, $start, $value, $orig, $condition);
                    } else {
                        yield $previous = new Token(T::VALUE | T::STRING | T::DOUBLE_QUOTED_STRING, $start, $value, $orig, $condition);
                    }
                    break;
                case '\'':
                    [$value, $orig] = $this->parseString($string, $position, $column, $row, $char);
                    yield $previous = new Token(T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, $start, $value, $orig, $condition);
                    break;
                case '`':
                    [$value, $orig] = $this->parseString($string, $position, $column, $row, $char);
                    yield $previous = new Token(T::NAME | T::BACKTICK_QUOTED_STRING, $start, $value, $orig, $condition);
                    break;
                case '.':
                    $next = $position < $length ? $string[$position] : '';
                    if (isset(self::$numbersKey[$next])) {
                        [$value, $orig] = $this->parseNumber($string, $position, $column, $row, '.');
                        if ($value !== null) {
                            yield $previous = new Token(T::VALUE | T::NUMBER, $start, $value, $orig, $condition);
                            break;
                        }
                    }
                    yield $previous = new Token(T::SYMBOL | T::DOT, $start, $char, null, $condition);
                    break;
                case '-':
                    $next = $position < $length ? $string[$position] : '';
                    if ($next === '-') {
                        $position++;
                        $column++;
                        $value = $char . $next;
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === "\n") {
                                $value .= $next;
                                $position++;
                                $column = 1;
                                $row++;
                                break;
                            } else {
                                $value .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        yield $previous = new Token(T::COMMENT | T::DOUBLE_HYPHEN_COMMENT, $start, $value, null, $condition);
                        break;
                    }
                    if (isset(self::$numbersKey[$next])) {
                        [$value, $orig] = $this->parseNumber($string, $position, $column, $row, '-');
                        if ($value !== null) {
                            yield $previous = new Token(T::VALUE | T::NUMBER, $start, $value, $orig, $condition);
                            break;
                        }
                    }
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (isset(self::$operatorSymbolsKey[$next])) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $value, null, $condition);
                    break;
                case '+':
                    $next = $position < $length ? $string[$position] : '';
                    if (isset(self::$numbersKey[$next])) {
                        [$value, $orig] = $this->parseNumber($string, $position, $column, $row, '+');
                        if ($value !== null) {
                            yield $previous = new Token(T::VALUE | T::NUMBER, $start, $value, $orig, $condition);
                            break;
                        }
                    }
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (isset(self::$operatorSymbolsKey[$next])) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $value, null, $condition);
                    break;
                case '0':
                    $next = $position < $length ? $string[$position] : '';
                    if ($next === 'b') {
                        $position++;
                        $column++;
                        $bits = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === '0' || $next === '1') {
                                $bits .= $next;
                                $position++;
                                $column++;
                            } else {
                                $orig = $char . 'b' . $bits;
                                yield $previous = new Token(T::VALUE | T::BINARY_LITERAL, $start, $bits, $orig, $condition);
                                break 2;
                            }
                        }
                    } elseif ($next === 'x') {
                        $position++;
                        $column++;
                        $bits = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if (isset(self::$hexadecKey[$next])) {
                                $bits .= $next;
                                $position++;
                                $column++;
                            } else {
                                $orig = $char . 'x' . $bits;
                                yield $previous = new Token(T::VALUE | T::HEXADECIMAL_LITERAL, $start, strtolower($bits), $orig, $condition);
                                break 2;
                            }
                        }
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
                    $value = substr($string, $position - 1, 36);
                    // UUID
                    if (strlen($value) === 36 && preg_match(self::UUID_REGEXP, $value)) {
                        $position += 35;
                        $column += 35;
                        yield $previous = new Token(T::VALUE | T::UUID, $start, $value, null, $condition);
                        break;
                    }
                    [$value, $orig] = $this->parseNumber($string, $position, $column, $row, $char);
                    if ($value !== null) {
                        yield $previous = new Token(T::VALUE | T::NUMBER, $start, $value, $orig, $condition);
                        break;
                    }
                    // continue
                case 'B':
                case 'b':
                    // b'01'
                    // B'01'
                    if (($char === 'B' || $char === 'b') && $position < $length && $string[$position] === '\'') {
                        $position++;
                        $column++;
                        $bits = '';
                        while ($position < $length) {
                            /** @var string $next */
                            $next = $string[$position];
                            if ($next === '0' || $next === '1') {
                                $bits .= $next;
                                $position++;
                                $column++;
                            } elseif ($next === '\'') {
                                $position++;
                                $column++;
                                $orig = $char . '\'' . $bits . '\'';
                                yield $previous = new Token(T::VALUE | T::BINARY_LITERAL, $start, $bits, $orig, $condition);
                                break;
                            } else {
                                throw new ExpectedTokenNotFoundException(''); // todo
                            }
                        }
                        break;
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
                        $value = substr($string, $position - 1, 36);
                        // UUID
                        if (strlen($value) === 36 && preg_match(self::UUID_REGEXP, $value)) {
                            $position += 35;
                            $column += 35;
                            yield $previous = new Token(T::VALUE | T::UUID, $start, $value, null, $condition);
                            break;
                        }
                    }
                    // continue
                case 'X':
                case 'x':
                    if (($char === 'X' || $char === 'x') && $position < $length && $string[$position] === '\'') {
                        $position++;
                        $column++;
                        $bits = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if (isset(self::$hexadecKey[$next])) {
                                $bits .= $next;
                                $position++;
                                $column++;
                            } elseif ($next === '\'') {
                                $position++;
                                $column++;
                                $orig = $char . '\'' . $bits . '\'';
                                if ((strlen($bits) % 2) === 1) {
                                    throw new ExpectedTokenNotFoundException(''); // todo
                                }
                                yield $previous = new Token(T::VALUE | T::HEXADECIMAL_LITERAL, $start, strtolower($bits), $orig, $condition);
                                break;
                            } else {
                                throw new ExpectedTokenNotFoundException(''); // todo
                            }
                        }
                        break;
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
                    // todo: charset declaration
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
                    while ($position < $length) {
                        $next = $string[$position];
                        if (isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    $upper = strtoupper($value);
                    if ($upper === 'NULL') {
                        yield $previous = new Token(T::KEYWORD | T::VALUE, $start, 'NULL', $value, $condition);
                    } elseif ($upper === 'TRUE') {
                        yield $previous = new Token(T::KEYWORD | T::VALUE, $start, 'TRUE', $value, $condition);
                    } elseif ($upper === 'FALSE') {
                        yield $previous = new Token(T::KEYWORD | T::VALUE, $start, 'FALSE', $value, $condition);
                    } elseif (isset($reservedKey[$upper])) {
                        if (isset($operatorKeywordsKey[$upper])) {
                            yield $previous = new Token(T::KEYWORD | T::RESERVED | T::OPERATOR, $start, $upper, $value, $condition);
                        } else {
                            yield $previous = new Token(T::KEYWORD | T::RESERVED, $start, $upper, $value, $condition);
                        }
                    } elseif (isset($keywordsKey[$upper])) {
                        yield $previous = new Token(T::KEYWORD | T::NAME | T::UNQUOTED_NAME, $start, $upper, $value, $condition);
                    } elseif ($upper === 'DELIMITER' && $this->settings->getPlatform()->hasUserDelimiter()) {
                        yield new Token(T::KEYWORD, $start, $upper, $value, $condition);
                        $start = $position;
                        $whitespace = $this->parseWhitespace($string, $position, $column, $row);
                        if ($this->withWhitespace) {
                            yield new Token(T::WHITESPACE, $start, $whitespace, null, $condition);
                        }
                        $start = $position;
                        $del = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === ';' || isset(self::$operatorSymbolsKey[$next])) {
                                $del .= $next;
                                $position++;
                                $column++;
                            } else {
                                break;
                            }
                        }
                        if ($del === '') {
                            throw new ExpectedTokenNotFoundException(''); // todo
                        }
                        $delimiter = $del;
                        $this->settings->setDelimiter($delimiter);
                        yield $previous = new Token(T::SYMBOL | T::DELIMITER_DEFINITION, $start, $delimiter, $condition);
                    } else {
                        yield $previous = new Token(T::NAME | T::UNQUOTED_NAME, $start, $value, $value, $condition);
                    }
                    break;
                case '_':
                    // todo: charset declaration
                default:
                    if (ord($char) < 32) {
                        throw new InvalidCharacterException($char, $start, ''); // todo
                    }
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    yield $previous = new Token(T::NAME | T::UNQUOTED_NAME, $start, $value, $value, $condition);
            }
        }
    }

    private function parseWhitespace(string &$string, int &$position, int &$column, int &$row): string
    {
        $length = strlen($string);
        $whitespace = '';
        while ($position < $length) {
            $next = $string[$position];
            if ($next === ' ' || $next === "\t" || $next === "\r") {
                $whitespace .= $next;
                $position++;
                $column++;
            } elseif ($next === "\n") {
                $whitespace .= $next;
                $position++;
                $column = 1;
                $row++;
            } else {
                break;
            }
        }

        return $whitespace;
    }

    /**
     * @param string $string
     * @param int $position
     * @param int $column
     * @param int $row
     * @param string $quote
     * @return string[] ($value, $orig)
     */
    private function parseString(string &$string, int &$position, int &$column, int &$row, string $quote): array
    {
        $length = strlen($string);
        $backslashes = !$this->settings->getMode()->contains(Mode::NO_BACKSLASH_ESCAPES);

        $orig = [$quote];
        $escaped = false;
        $finished = false;
        while ($position < $length) {
            $next = $string[$position];
            if ($next === $quote) {
                $orig[] = $next;
                $position++;
                $column++;
                if ($escaped) {
                    $escaped = false;
                } elseif ($position < $length && $string[$position] === $quote) {
                    $escaped = true;
                } else {
                    $finished = true;
                    break;
                }
            } elseif ($next === "\n") {
                $orig[] = $next;
                $position++;
                $column = 1;
                $row++;
            } elseif ($backslashes && $next === '\\') {
                $escaped = !$escaped;
                $orig[] = $next;
                $position++;
                $column++;
            } elseif ($escaped && $next !== '\\' && $next !== $quote) {
                $escaped = false;
                $orig[] = $next;
                $position++;
                $column++;
            } else {
                $orig[] = $next;
                $position++;
                $column++;
            }
        }
        if (!$finished) {
            throw new EndOfStringNotFoundException(''); // todo
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
     * \0   An ASCII NUL (X'00') character
     * \'   A single quote (') character
     * \"   A double quote (") character
     * \b   A backspace character
     * \n   A newline (linefeed) character
     * \r   A carriage return character
     * \t   A tab character
     * \Z   ASCII 26 (Control+Z)
     * \\   A backslash (\) character
     *
     * (do not unescape. keep original for LIKE)
     * \%   A % character
     * \_   A _ character
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

            // todo: ???
        }

        return $string;
    }

    /**
     * @param string $string
     * @param int $position
     * @param int $column
     * @param int $row
     * @param string $start
     * @return int[]|float[]|string[]|null[] (int|float|string|null $value, string|null $orig)
     */
    private function parseNumber(string &$string, int &$position, int &$column, int &$row, string $start): array
    {
        $length = strlen($string);
        $offset = 0;
        $num = isset(self::$numbersKey[$start]);
        $base = $start;
        $exp = '';
        do {
            // integer
            $next = '';
            while ($position + $offset < $length) {
                $next = $string[$position + $offset];
                if (isset(self::$numbersKey[$next])) {
                    $base .= $next;
                    $offset++;
                    $num = true;
                } else {
                    break;
                }
            }
            if ($position + $offset >= $length) {
                break;
            }

            // decimal part
            if ($next === '.') {
                if ($start !== '.') {
                    $base .= $next;
                    $offset++;
                    while ($position + $offset < $length) {
                        $next = $string[$position + $offset];
                        if (isset(self::$numbersKey[$next])) {
                            $base .= $next;
                            $offset++;
                            $num = true;
                        } else {
                            break;
                        }
                    }
                } else {
                    break;
                }
            }
            if ($position + $offset >= $length) {
                break;
            }

            // exponent
            $next = $string[$position + $offset];
            do {
                if ($next === 'e' || $next === 'E') {
                    $exp = $next;
                    $offset++;
                    $next = $position + $offset < $length ? $string[$position + $offset] : '';
                    $expComplete = false;
                    if ($next === '+' || $next === '-' || isset(self::$numbersKey[$next])) {
                        $exp .= $next;
                        $offset++;
                        if (isset(self::$numbersKey[$next])) {
                            $expComplete = true;
                        }
                    }
                    while ($position + $offset < $length) {
                        $next = $string[$position + $offset];
                        if (isset(self::$numbersKey[$next])) {
                            $exp .= $next;
                            $offset++;
                            $expComplete = true;
                        } else {
                            if (trim($exp, 'e+-') === '' && strpos($base, '.') !== false) {
                                throw new ExpectedTokenNotFoundException(''); // todo
                            }
                            break;
                        }
                    }
                    if (!$expComplete) {
                        throw new ExpectedTokenNotFoundException(''); // todo
                    }
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
        $position += $len;
        $column += $len;

        return [$value, $orig];
    }

}
