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

namespace SqlFtw\Parser;

use Dogma\Re;
use Dogma\Str;
use Dogma\StrictBehaviorMixin;
use Generator;
use SqlFtw\Parser\TokenType as T;
use SqlFtw\Platform\Mode;
use SqlFtw\Platform\Platform;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Keyword;
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
 * - quoted delimiters : E
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

    /** @var array<string, int> (this is in fact array<int, int>, but PHPStan is unable to cope with the auto-casting of numeric string keys) */
    private static $numbersKey;

    /** @var array<string, int> */
    private static $hexadecKey;

    /** @var array<string, int> */
    private static $nameCharsKey;

    /** @var array<string, int> */
    private static $operatorSymbolsKey;

    /** @var PlatformSettings */
    private $settings;

    /** @var Platform */
    private $platform;

    /** @var bool */
    private $withComments;

    /** @var bool */
    private $withWhitespace;

    /** @var array<string, int> */
    private $reservedKey;

    /** @var array<string, int> */
    private $keywordsKey;

    /** @var array<string, int> */
    private $operatorsKey;

    /** @var array<string, int> */
    private $operatorKeywordsKey;

    /** @var array<string, int> */
    private $functionsKey;

    public function __construct(
        PlatformSettings $settings,
        bool $withComments = true,
        bool $withWhitespace = false
    ) {
        if (self::$numbersKey === null) {
            self::$numbersKey = array_flip(self::NUMBERS); // @phpstan-ignore-line
            self::$hexadecKey = array_flip(array_merge(self::NUMBERS, ['A', 'a', 'B', 'b', 'C', 'c', 'D', 'd', 'E', 'e', 'F', 'f']));
            self::$nameCharsKey = array_flip(array_merge(self::LETTERS, self::NUMBERS, ['$', '_']));
            self::$operatorSymbolsKey = array_flip(self::OPERATOR_SYMBOLS);
        }

        $this->settings = $settings;
        $this->platform = $settings->getPlatform();
        $this->withComments = $withComments;
        $this->withWhitespace = $withWhitespace;

        $features = $this->platform->getFeatures();
        $this->reservedKey = array_flip($features->getReservedWords());
        $this->keywordsKey = array_flip($features->getNonReservedWords());
        $this->operatorsKey = array_flip($features->getOperators());
        $this->operatorKeywordsKey = array_flip($features->getOperatorKeywords());
        $this->functionsKey = array_flip($features->getBuiltInFunctions());
    }

    /**
     * Tokenize SQL code. Expects line endings to be converted to "\n" and UTF-8 encoding.
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
     * @return Token[]|Generator
     */
    public function tokenize(string $string): Generator
    {
        $length = strlen($string);
        $position = 0;
        $row = 1;
        $column = 1;

        $delimiter = $this->settings->getDelimiter();
        // last significant token parsed (comments and whitespace are skipped here)
        $previous = new Token(TokenType::END, 0);
        $condition = null;

        while ($position < $length) {
            $uuidCheck = false;
            $char = $string[$position];
            $start = $position;
            $position++;
            $column++;

            if ($char === $delimiter[0]) {
                if (substr($string, $position - 1, strlen($delimiter)) === $delimiter) {
                    $position += strlen($delimiter) - 1;
                    yield new Token(T::SYMBOL | T::DELIMITER, $start, $delimiter, null, $condition);
                    continue;
                }
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
                    if ($position < $length && $condition !== null && $string[$position] === '/') {
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
                        if (!isset($this->operatorsKey[$value . $next])) {
                            yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $value, null, $condition);
                            break 2;
                        }
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
                    yield $previous = new Token(T::VALUE | T::PLACEHOLDER, $start, $char, null, $condition);
                    break;
                case '@':
                    if (($previous->type & (T::STRING | T::NAME)) !== 0 && ($previous->type & (T::KEYWORD | T::UNQUOTED_NAME)) !== (T::KEYWORD | T::UNQUOTED_NAME)) {
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
                    yield $previous = new Token(T::NAME | T::AT_VARIABLE, $start, $value, null, $condition);
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
                        if (preg_match('~^[Mm]?!(?:[0-9]{5,6})?~', $string, $m, 0, $position) === 1) {
                            $versionId = strtoupper(str_replace('!', '', $m[0]));
                            if ($this->platform->interpretOptionalComment($versionId)) {
                                $condition = $versionId;
                                $position += strlen($versionId) + 1;
                                $column += strlen($versionId) + 1;
                                // continue parsing as conditional code
                                break;
                            }
                        }

                        // parse as a regular comment
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

                        if ($this->withComments) {
                            if ($value[2] === '!' || ($value[3] === '!' && ($value[2] === 'm' || $value[2] === 'M'))) {
                                // /*!12345 comment (when not interpreted as code) */
                                yield new Token(T::COMMENT | T::BLOCK_COMMENT | T::OPTIONAL_COMMENT, $start, $value);
                            } elseif ($value[2] === '+') {
                                // /*+ comment */
                                yield new Token(T::COMMENT | T::BLOCK_COMMENT | T::HINT_COMMENT, $start, $value);
                            } else {
                                // /* comment */
                                yield new Token(T::COMMENT | T::BLOCK_COMMENT, $start, $value);
                            }
                        }
                    } else {
                        yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $char, null, $condition);
                    }
                    break;
                case '"':
                    [$value, $orig] = $this->parseString($string, $position, $column, $row, $char);
                    if ($this->settings->getMode()->containsAny(Mode::ANSI_QUOTES)) {
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
                    $numberCanFollow = ($previous->type & (T::SYMBOL | T::RIGHT_PARENTHESIS)) === T::SYMBOL
                        || ($previous->type & T::END) !== 0
                        || (($previous->type & T::KEYWORD) !== 0 && $previous->value === Keyword::DEFAULT);
                    if ($numberCanFollow && isset(self::$numbersKey[$next])) {
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
                    $numberCanFollow = ($previous->type & (T::SYMBOL | T::RIGHT_PARENTHESIS)) === T::SYMBOL
                        || ($previous->type & T::END) !== 0
                        || (($previous->type & T::KEYWORD) !== 0 && $previous->value === Keyword::DEFAULT);
                    if ($numberCanFollow && isset(self::$numbersKey[$next])) {
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
                    if (strlen($value) === 36 && Re::match($value, self::UUID_REGEXP) !== null) {
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
                        if (strlen($value) === 36 && Re::match($value, self::UUID_REGEXP) !== null) {
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
                    $yieldDelimiter = false;
                    if (Str::endsWith($value, $delimiter)) {
                        // fucking name-like delimiter after name without whitespace
                        $value = substr($value, 0, -strlen($delimiter));
                        $yieldDelimiter = true;
                    }

                    $upper = strtoupper($value);
                    if ($upper === Keyword::NULL) {
                        yield $previous = new Token(T::KEYWORD | T::VALUE, $start, Keyword::NULL, $value, $condition);
                    } elseif ($upper === Keyword::TRUE) {
                        yield $previous = new Token(T::KEYWORD | T::VALUE, $start, Keyword::TRUE, $value, $condition);
                    } elseif ($upper === Keyword::FALSE) {
                        yield $previous = new Token(T::KEYWORD | T::VALUE, $start, Keyword::FALSE, $value, $condition);
                    } elseif (isset($this->reservedKey[$upper])) {
                        if (isset($this->operatorKeywordsKey[$upper])) {
                            yield $previous = new Token(T::KEYWORD | T::RESERVED | T::OPERATOR, $start, $upper, $value, $condition);
                        } elseif (isset($this->functionsKey[$upper])) {
                            yield $previous = new Token(T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME, $start, $upper, $value, $condition);
                        } else {
                            yield $previous = new Token(T::KEYWORD | T::RESERVED, $start, $upper, $value, $condition);
                        }
                    } elseif (isset($this->keywordsKey[$upper])) {
                        yield $previous = new Token(T::KEYWORD | T::NAME | T::UNQUOTED_NAME, $start, $upper, $value, $condition);
                    } elseif ($upper === Keyword::DELIMITER && $this->platform->userDelimiter()) {
                        yield new Token(T::KEYWORD, $start, $upper, $value, $condition);
                        $start = $position;
                        $whitespace = $this->parseWhitespace($string, $position, $column, $row);
                        $whitespace = new Token(T::WHITESPACE, $start, $whitespace, null, $condition);
                        if ($this->withWhitespace) {
                            yield $whitespace;
                        }
                        $start = $position;
                        $del = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === "\n") {
                                break;
                            } else {
                                $del .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        if ($del === '') {
                            throw new ExpectedTokenNotFoundException('Delimiter not found'); // todo
                        }
                        if ($this->settings->getPlatform()->getFeatures()->isReserved(strtoupper($del))) {
                            throw new ExpectedTokenNotFoundException('Delimiter can not be a reserved word found.'); // todo
                        }
                        // todo: quoted delimiters :E
                        /*
                         * The delimiter string can be specified as an unquoted or quoted argument on the delimiter command line.
                         * Quoting can be done with either single quote ('), double quote ("), or backtick (`) characters.
                         * To include a quote within a quoted string, either quote the string with a different quote character
                         * or escape the quote with a backslash (\) character. Backslash should be avoided outside of quoted
                         * strings because it is the escape character for MySQL. For an unquoted argument, the delimiter is read
                         * up to the first space or end of line. For a quoted argument, the delimiter is read up to the matching quote on the line.
                         */
                        $delimiter = $del;
                        $this->settings->setDelimiter($delimiter);
                        yield $previous = new Token(T::SYMBOL | T::DELIMITER_DEFINITION, $start, $delimiter, $condition);
                    } else {
                        yield $previous = new Token(T::NAME | T::UNQUOTED_NAME, $start, $value, $value, $condition);
                    }

                    if ($yieldDelimiter) {
                        yield new Token(T::SYMBOL | T::DELIMITER, $start, $delimiter, null, $condition);
                    }
                    break;
                case '_':
                    $value = '';
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
                    if ($value !== '' && !Charset::validateValue($value)) {
                        throw new LexerException("Invalid string charset declaration: $value");
                    }
                    // todo: ignored - do something about it
                    break;
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
     * @return string[] ($value, $orig)
     */
    private function parseString(string &$string, int &$position, int &$column, int &$row, string $quote): array
    {
        $length = strlen($string);
        $backslashes = !$this->settings->getMode()->containsAny(Mode::NO_BACKSLASH_ESCAPES);

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
        if (!$this->settings->getMode()->containsAny(Mode::NO_BACKSLASH_ESCAPES)) {
            $string = str_replace(array_keys($translations), array_values($translations), $string);

            // todo: ???
        }

        return $string;
    }

    /**
     * @return mixed[]|array{int|float|string|null, string|null}
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
            } while (false); // @phpstan-ignore-line
        } while (false); // @phpstan-ignore-line

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
