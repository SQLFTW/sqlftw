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
// phpcs:disable SlevomatCodingStandard.ControlStructures.AssignmentInCondition

namespace SqlFtw\Parser;

use Dogma\StrictBehaviorMixin;
use Generator;
use SqlFtw\Parser\TokenType as T;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlMode;
use function array_flip;
use function array_keys;
use function array_merge;
use function array_values;
use function ctype_digit;
use function implode;
use function ltrim;
use function ord;
use function preg_match;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use function trim;

/**
 * todo:
 * - quoted delimiters
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

    private const MYSQL_ESCAPES = [
        '\\0' => "\x00",
        "\\'" => "'",
        '\\"' => '"',
        '\\b' => "\x08",
        '\\n' => "\n",
        '\\r' => "\r",
        '\\t' => "\t",
        '\\Z' => "\x1A",
        '\\\\' => '\\',
    ];

    public const UUID_REGEXP = '~^[\dA-F]{8}-[\dA-F]{4}-[\dA-F]{4}-[\dA-F]{4}-[\dA-F]{12}$~i';
    public const IP_V4_REGEXP = '~^((?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d))~';

    /** @var array<string, int> (this is in fact array<int, int>, but PHPStan is unable to cope with the auto-casting of numeric string keys) */
    private static $numbersKey;

    /** @var array<string, int> */
    private static $hexadecKey;

    /** @var array<string, int> */
    private static $nameCharsKey;

    /** @var array<string, int> */
    private static $userVariableNameCharsKey;

    /** @var array<string, int> */
    private static $operatorSymbolsKey;

    /** @var ParserSettings */
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

    /** @var string[] */
    private $escapeKeys;

    /** @var string[] */
    private $escapeValues;

    public function __construct(
        ParserSettings $settings,
        bool $withComments = true,
        bool $withWhitespace = false
    ) {
        if (self::$numbersKey === null) {
            self::$numbersKey = array_flip(self::NUMBERS); // @phpstan-ignore-line
            self::$hexadecKey = array_flip(array_merge(self::NUMBERS, ['A', 'a', 'B', 'b', 'C', 'c', 'D', 'd', 'E', 'e', 'F', 'f']));
            self::$nameCharsKey = array_flip(array_merge(self::LETTERS, self::NUMBERS, ['$', '_']));
            self::$userVariableNameCharsKey = array_flip(array_merge(self::LETTERS, self::NUMBERS, ['$', '_', '.']));
            self::$operatorSymbolsKey = array_flip(self::OPERATOR_SYMBOLS);
        }

        $this->settings = $settings;
        $this->platform = $settings->getPlatform();
        $this->withComments = $withComments;
        $this->withWhitespace = $withWhitespace;

        $this->reservedKey = array_flip($this->platform->getReserved());
        $this->keywordsKey = array_flip($this->platform->getNonReserved());
        $this->operatorsKey = array_flip($this->platform->getOperators());
        $this->escapeKeys = array_keys(self::MYSQL_ESCAPES);
        $this->escapeValues = array_values(self::MYSQL_ESCAPES);
    }

    /**
     * @return Generator<TokenList>
     */
    public function tokenizeLists(string $string): Generator
    {
        return $this->slice($this->tokenize($string));
    }

    /**
     * @param iterable<Token> $tokens
     * @return Generator<TokenList>
     */
    private function slice(iterable $tokens): Generator
    {
        // this allows TokenList to not have to call doAutoSkip() million times when there are no skippable tokens produces
        $autoSkip = ($this->withWhitespace ? T::WHITESPACE : 0) | ($this->withComments ? T::COMMENT : 0);

        $buffer = [];
        $invalid = false;
        foreach ($tokens as $token) {
            if (($token->type & TokenType::INVALID) !== 0) {
                $invalid = true;
            }

            $buffer[] = $token;

            if (($token->type & TokenType::DELIMITER) !== 0) {
                yield new TokenList($buffer, $this->settings, $autoSkip, $invalid);

                $invalid = false;
                $buffer = [];
            } elseif (($token->type & TokenType::DELIMITER_DEFINITION) !== 0) {
                yield new TokenList($buffer, $this->settings, $autoSkip, $invalid);

                $invalid = false;
                $buffer = [];
            }
        }
        if ($buffer !== []) {
            yield new TokenList($buffer, $this->settings, $autoSkip, $invalid);
        }
    }

    /**
     * Tokenize SQL code and return a generator of Token objects.
     * @return Generator<Token>
     */
    public function tokenize(string $string): Generator
    {
        // last significant token parsed (comments and whitespace are skipped here)
        $previous = new Token(TokenType::END, 0, 0, '');
        $delimiter = $this->settings->getDelimiter();
        $commentDepth = 0;
        $condition = null;

        $length = strlen($string);
        $position = 0;
        $row = 1;
        $column = 1;

        while ($position < $length) {
            $char = $string[$position];
            $start = $position;
            $position++;
            $column++;

            if ($char === $delimiter[0]) {
                if (substr($string, $position - 1, strlen($delimiter)) === $delimiter) {
                    $position += strlen($delimiter) - 1;
                    yield new Token(T::DELIMITER, $start, $row, $delimiter, null);
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
                        yield new Token(T::WHITESPACE, $start, $row, $value, null);
                    }
                    break;
                case '(':
                case ')':
                case '[':
                case ']':
                case '{':
                case '}':
                case ',':
                case ';':
                    yield $previous = new Token(T::SYMBOL, $start, $row, $char, null);
                    break;
                case ':':
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (!isset($this->operatorsKey[$value . $next])) {
                            if ($value !== ':') {
                                yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $row, $value, null);
                            } else {
                                yield $previous = new Token(T::SYMBOL, $start, $row, $char, null);
                            }
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
                    if ($value !== ':') {
                        yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $row, $value, null);
                    } else {
                        yield $previous = new Token(T::SYMBOL, $start, $row, $char, null);
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
                case '\\':
                    if ($char === '\\' && $position < $length && $string[$position] === 'N') {
                        $position++;
                        $column++;
                        yield $previous = new Token(T::SYMBOL | T::VALUE, $start, $row, '\\N', null);
                        break;
                    }
                    // continue
                case '!':
                case '%':
                case '&':
                case '<':
                case '=':
                case '>':
                case '^':
                case '|':
                case '~':
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (!isset($this->operatorsKey[$value . $next])) {
                            yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $row, $value, null);
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
                    yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $row, $value, null);
                    break;
                case '?':
                    yield $previous = new Token(T::VALUE | T::PLACEHOLDER, $start, $row, $char, null);
                    break;
                case '@':
                    $value = $char;
                    $second = $string[$position];
                    if ($second === '@') {
                        // @@variable
                        $value .= $second;
                        $position++;
                        $column++;
                        if ($string[$position] === '`') {
                            // @@`variable`
                            $position++;
                            $column++;
                            yield $previous = $this->parseString(T::NAME | T::AT_VARIABLE, $string, $position, $column, $row, '`', '@@');
                            break;
                        }
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

                        $yieldDelimiter = false;
                        if (substr($value, -strlen($delimiter)) === $delimiter) { // str_ends_with()
                            // fucking name-like delimiter after name without whitespace
                            $value = substr($value, 0, -strlen($delimiter));
                            $yieldDelimiter = true;
                        }
                        $upper = strtoupper(substr($value, 2));
                        if ($upper === 'DEFAULT') {
                            // todo: probably all magic functions?
                            $exception = new LexerException("Invalid variable name $value.", $position, $string);

                            yield new Token(T::NAME | T::AT_VARIABLE | T::INVALID, $start, $row, $value, null, $exception);
                            break;
                        }

                        yield $previous = new Token(T::NAME | T::AT_VARIABLE, $start, $row, $value, null);

                        if ($yieldDelimiter) {
                            yield new Token(T::DELIMITER, $start, $row, $delimiter, null);
                        }
                    } elseif ($second === '`' || $second === "'" || $second === '"') {
                        $position++;
                        $column++;
                        yield $previous = $this->parseString(T::NAME | T::AT_VARIABLE, $string, $position, $column, $row, $second, '@');
                    } elseif (isset(self::$userVariableNameCharsKey[$second]) || ord($second) > 127) {
                        // @variable
                        $value .= $second;
                        $position++;
                        $column++;
                        while ($position < $length) {
                            $next = $string[$position];
                            if (isset(self::$userVariableNameCharsKey[$next]) || ord($next) > 127) {
                                $value .= $next;
                                $position++;
                                $column++;
                            } else {
                                break;
                            }
                        }

                        $yieldDelimiter = false;
                        if (substr($value, -strlen($delimiter)) === $delimiter) { // str_ends_with()
                            // fucking name-like delimiter after name without whitespace
                            $value = substr($value, 0, -strlen($delimiter));
                            $yieldDelimiter = true;
                        }
                        $upper = strtoupper(substr($value, 1));
                        if ($upper === 'DEFAULT') {
                            // todo: probably all magic functions?
                            $exception = new LexerException("Invalid variable name $value.", $position, $string);

                            yield new Token(T::NAME | T::AT_VARIABLE | T::INVALID, $start, $row, $value, null, $exception);
                            break;
                        }

                        yield $previous = new Token(T::NAME | T::AT_VARIABLE, $start, $row, $value, null);

                        if ($yieldDelimiter) {
                            yield new Token(T::DELIMITER, $start, $row, $delimiter, null);
                        }
                    } else {
                        // simple @ (valid as empty host name)
                        yield $previous = new Token(T::NAME | T::AT_VARIABLE, $start, $row, $value, null);
                        break;
                    }
                    break;
                case '#':
                    // # comment
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if ($next === "\n") {
                            break;
                        } else {
                            $value .= $next;
                            $position++;
                            $column++;
                        }
                    }
                    if ($this->withComments) {
                        yield $previous = new Token(T::COMMENT | T::HASH_COMMENT, $start, $row, $value, null);
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
                                break;
                            } else {
                                $value .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        if ($this->withComments) {
                            yield $previous = new Token(T::COMMENT | T::DOUBLE_SLASH_COMMENT, $start, $row, $value, null);
                        }
                    } elseif ($next === '*') {
                        $position++;
                        $column++;

                        $optional = $string[$position] === '!';
                        // todo: Maria
                        $validOptional = true;
                        if ($optional) {
                            $validOptional = preg_match('~^[Mm]?!(?:00000|[1-9]\d{4,5})?(?: |\\*/)~', substr($string, $position, 10), $m) === 1;
                            if ($validOptional) {
                                $versionId = strtoupper(str_replace('!', '', $m[0]));
                                if ($this->platform->interpretOptionalComment($versionId)) {
                                    $condition = $versionId;
                                    $position += strlen($versionId) + 1;
                                    $column += strlen($versionId) + 1;
                                    // continue parsing as conditional code
                                    break;
                                }
                            }
                        }

                        $hint = $string[$position] === '+';

                        // parse as a regular comment
                        $commentDepth++;
                        $value = $char . $next;
                        $terminated = false;
                        while ($position < $length) {
                            $next = $string[$position];
                            if (!$hint && $next === '/' && ($position + 1 < $length) && $string[$position + 1] === '*') {
                                $value .= $next . $string[$position + 1];
                                $position += 2;
                                $column += 2;
                                $commentDepth++;
                            } elseif ($next === '*' && ($position + 1 < $length) && $string[$position + 1] === '/') {
                                $value .= $next . $string[$position + 1];
                                $position += 2;
                                $column += 2;
                                $commentDepth--;
                                if ($commentDepth === 0) {
                                    $terminated = true;
                                    break;
                                }
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
                        if (!$terminated) {
                            $exception = new LexerException('End of comment not found.', $position, $string);

                            yield new Token(T::COMMENT | T::BLOCK_COMMENT | T::INVALID, $start, $row, $value, null, $exception);
                            break;
                        } elseif (!$validOptional) {
                            $exception = new LexerException('Invalid optional comment: ' . $value, $position, $string);

                            yield new Token(T::COMMENT | T::BLOCK_COMMENT | T::OPTIONAL_COMMENT | T::INVALID, $start, $row, $value, null, $exception);
                            break;
                        }

                        if ($this->withComments) {
                            if ($optional) {
                                // /*!12345 comment (when not interpreted as code) */
                                yield new Token(T::COMMENT | T::BLOCK_COMMENT | T::OPTIONAL_COMMENT, $start, $row, $value);
                            } elseif ($hint) {
                                // /*+ comment */
                                yield new Token(T::COMMENT | T::BLOCK_COMMENT | T::HINT_COMMENT, $start, $row, $value);
                            } else {
                                // /* comment */
                                yield new Token(T::COMMENT | T::BLOCK_COMMENT, $start, $row, $value);
                            }
                        }
                    } else {
                        yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $row, $char, null);
                    }
                    break;
                case '"':
                    $type = $this->settings->getMode()->containsAny(SqlMode::ANSI_QUOTES)
                        ? T::NAME | T::DOUBLE_QUOTED_STRING
                        : T::VALUE | T::STRING | T::DOUBLE_QUOTED_STRING;

                    yield $previous = $this->parseString($type, $string, $position, $column, $row, '"');
                    break;
                case "'":
                    yield $previous = $this->parseString(T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, $string, $position, $column, $row, "'");
                    break;
                case '`':
                    yield $previous = $this->parseString(T::NAME | T::BACKTICK_QUOTED_STRING, $string, $position, $column, $row, '`');
                    break;
                case '.':
                    $next = $position < $length ? $string[$position] : '';
                    if (isset(self::$numbersKey[$next])) {
                        $token = $this->parseNumber($string, $position, $column, $row, '.');
                        if ($token !== null) {
                            yield $previous = $token;
                            break;
                        }
                    }
                    yield $previous = new Token(T::SYMBOL, $start, $row, $char, null);
                    break;
                case '-':
                    $second = $position < $length ? $string[$position] : '';
                    $numberCanFollow = ($previous->type & T::END) !== 0
                        || (($previous->type & T::SYMBOL) !== 0 && $previous->value !== ')')
                        || (($previous->type & T::KEYWORD) !== 0 && strtoupper($previous->value) === Keyword::DEFAULT);
                    if ($numberCanFollow) {
                        $token = $this->parseNumber($string, $position, $column, $row, '-');
                        if ($token !== null) {
                            yield $previous = $token;
                            break;
                        }
                    }

                    if ($second === '-') {
                        $third = $position + 1 < $length ? $string[$position + 1] : '';

                        if ($third === ' ') {
                            // -- comment
                            $endOfLine = strpos($string, "\n", $position);
                            if ($endOfLine === false) {
                                $endOfLine = strlen($string);
                            }
                            $line = substr($string, $position - 1, $endOfLine - $position + 1);
                            $position += strlen($line) - 1;
                            $column += strlen($line) - 1;

                            if ($this->withComments) {
                                yield $previous = new Token(T::COMMENT | T::DOUBLE_HYPHEN_COMMENT, $start, $row, $line, null);
                            }
                            break;
                        }

                        yield new Token(T::SYMBOL | T::OPERATOR, $start, $row, '-', null);
                        $position++;
                        $column++;

                        $token = $this->parseNumber($string, $position, $column, $row, '-');
                        if ($token !== null) {
                            yield $previous = $token;
                            break;
                        }
                    }

                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (!isset($this->operatorsKey[$value . $next])) {
                            yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $row, $value, null);
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
                    yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $row, $value, null);
                    break;
                case '+':
                    $next = $position < $length ? $string[$position] : '';
                    $numberCanFollow = ($previous->type & T::END) !== 0
                        || (($previous->type & T::SYMBOL) !== 0 && $previous->value !== ')')
                        || (($previous->type & T::KEYWORD) !== 0 && $previous->value === Keyword::DEFAULT);
                    if ($numberCanFollow && isset(self::$numbersKey[$next])) {
                        $token = $this->parseNumber($string, $position, $column, $row, '+');
                        if ($token !== null) {
                            yield $previous = $token;
                            break;
                        }
                    }

                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (!isset($this->operatorsKey[$value . $next])) {
                            yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $row, $value, null);
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
                    yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $row, $value, null);
                    break;
                case '0':
                    $next = $position < $length ? $string[$position] : '';
                    if ($next === 'b') {
                        // 0b00100011
                        $position++;
                        $column++;
                        $bits = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === '0' || $next === '1') {
                                $bits .= $next;
                                $position++;
                                $column++;
                            } elseif (isset(self::$nameCharsKey[$next])) {
                                // name pretending to be a binary literal :E
                                $position -= strlen($bits) + 1;
                                $column -= strlen($bits) + 1;
                                break;
                            } else {
                                $orig = $char . 'b' . $bits;
                                yield $previous = new Token(T::VALUE | T::BINARY_LITERAL, $start, $row, $bits, $orig);
                                break 2;
                            }
                        }
                    } elseif ($next === 'x') {
                        // 0x001f
                        $position++;
                        $column++;
                        $bits = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if (isset(self::$hexadecKey[$next])) {
                                $bits .= $next;
                                $position++;
                                $column++;
                            } elseif (isset(self::$nameCharsKey[$next])) {
                                // name pretending to be a hexadecimal literal :E
                                $position -= strlen($bits) + 1;
                                $column -= strlen($bits) + 1;
                                break;
                            } else {
                                $orig = $char . 'x' . $bits;
                                yield $previous = new Token(T::VALUE | T::HEXADECIMAL_LITERAL, $start, $row, strtolower($bits), $orig);
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
                    $value = substr($string, $position - 1, 36);
                    // UUID
                    if (strlen($value) === 36 && preg_match(self::UUID_REGEXP, $value) !== 0) {
                        $position += 35;
                        $column += 35;
                        yield $previous = new Token(T::VALUE | T::UUID, $start, $row, $value, null);
                        break;
                    }
                    // IPv4
                    if (preg_match(self::IP_V4_REGEXP, $value, $m) !== 0) {
                        $position += strlen($m[0]) - 1;
                        $column += strlen($m[0]) - 1;
                        yield $previous = new Token(T::VALUE | T::STRING, $start, $row, $m[0], null);
                        break;
                    }
                    $token = $this->parseNumber($string, $position, $column, $row, $char);
                    if ($token !== null) {
                        yield $previous = $token;
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
                        $bits = $next = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === '\'') {
                                $position++;
                                $column++;
                                break;
                            } else {
                                $bits .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        if (ltrim($bits, '01') === '') {
                            $orig = $char . '\'' . $bits . '\'';

                            yield $previous = new Token(T::VALUE | T::BINARY_LITERAL, $start, $row, $bits, $orig);
                        } else {
                            $exception = new LexerException('Invalid binary literal', $position, $string);
                            $orig = $char . '\'' . $bits . $next;

                            yield $previous = new Token(T::VALUE | T::BINARY_LITERAL | T::INVALID, $start, $row, $orig, $orig, $exception);
                            break;
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
                    $value = substr($string, $position - 1, 36);
                    // UUID
                    if (strlen($value) === 36 && preg_match(self::UUID_REGEXP, $value) !== 0) {
                        $position += 35;
                        $column += 35;
                        yield $previous = new Token(T::VALUE | T::UUID, $start, $row, $value, null);
                        break;
                    }
                    // continue
                case 'X':
                case 'x':
                    if (($char === 'X' || $char === 'x') && $position < $length && $string[$position] === '\'') {
                        $position++;
                        $column++;
                        $bits = $next = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === '\'') {
                                $position++;
                                $column++;
                                break;
                            } else {
                                $bits .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        $bits = strtolower($bits);
                        if (ltrim($bits, '0123456789abcdef') === '') {
                            $orig = $char . '\'' . $bits . '\'';

                            yield $previous = new Token(T::VALUE | T::HEXADECIMAL_LITERAL, $start, $row, $bits, $orig);
                        } else {
                            $exception = new LexerException('Invalid hexadecimal literal', $position, $string);
                            $orig = $char . '\'' . $bits . $next;

                            yield $previous = new Token(T::VALUE | T::HEXADECIMAL_LITERAL | T::INVALID, $start, $row, $orig, $orig, $exception);
                            break;
                        }
                        break;
                    }
                    // continue
                case 'N':
                    $next = $position < $length ? $string[$position] : null;
                    if ($char === 'N' && $next === '"') {
                        $position++;
                        $column++;
                        $type = $this->settings->getMode()->containsAny(SqlMode::ANSI_QUOTES)
                            ? T::NAME | T::DOUBLE_QUOTED_STRING
                            : T::VALUE | T::STRING | T::DOUBLE_QUOTED_STRING;

                        yield $previous = $this->parseString($type, $string, $position, $column, $row, '"', 'N');
                        break;
                    } elseif ($char === 'N' && $next === "'") {
                        $position++;
                        $column++;
                        yield $previous = $this->parseString(T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, $string, $position, $column, $row, "'", 'N');
                        break;
                    } elseif ($char === 'N' && $next === '`') {
                        $position++;
                        $column++;
                        yield $previous = $this->parseString(T::NAME | T::BACKTICK_QUOTED_STRING, $string, $position, $column, $row, "`", 'N');
                        break;
                    }
                case 'n':
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
                case '_':
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
                    if (substr($value, -strlen($delimiter)) === $delimiter) { // str_ends_with()
                        // fucking name-like delimiter after name without whitespace
                        $value = substr($value, 0, -strlen($delimiter));
                        $yieldDelimiter = true;
                    }

                    $upper = strtoupper($value);
                    if (isset($this->reservedKey[$upper])) {
                        if (isset($this->operatorsKey[$upper])) {
                            yield $previous = new Token(T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME | T::OPERATOR, $start, $row, $value, null);
                        } else {
                            yield $previous = new Token(T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME, $start, $row, $value, null);
                        }
                    } elseif (isset($this->keywordsKey[$upper])) {
                        yield $previous = new Token(T::KEYWORD | T::NAME | T::UNQUOTED_NAME, $start, $row, $value, null);
                    } elseif ($upper === Keyword::DELIMITER && $this->platform->userDelimiter()) {
                        yield new Token(T::KEYWORD | T::NAME | T::UNQUOTED_NAME, $start, $row, $value, null);
                        $start = $position;
                        $whitespace = $this->parseWhitespace($string, $position, $column, $row);
                        if ($this->withWhitespace) {
                            yield new Token(T::WHITESPACE, $start, $row, $whitespace, null);
                        }
                        $start = $position;
                        $del = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === "\n" || $next === "\r" || $next === "\t" || $next === ' ') {
                                break;
                            } else {
                                $del .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        if ($del === '') {
                            $exception = new LexerException('Delimiter not found', $position, $string);

                            yield $previous = new Token(T::INVALID, $start, $row, $del, $del, $exception);
                            break;
                        }
                        if ($this->settings->getPlatform()->isReserved(strtoupper($del))) {
                            $exception = new LexerException('Delimiter can not be a reserved word', $position, $string);

                            yield $previous = new Token(T::DELIMITER_DEFINITION | T::INVALID, $start, $row, $del, $del, $exception);
                            break;
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
                        yield $previous = new Token(T::DELIMITER_DEFINITION, $start, $row, $delimiter, null);
                    } else {
                        yield $previous = new Token(T::NAME | T::UNQUOTED_NAME, $start, $row, $value, null);
                    }

                    if ($yieldDelimiter) {
                        yield new Token(T::DELIMITER, $start, $row, $delimiter, null);
                    }
                    break;
                default:
                    if (ord($char) < 32) {
                        $exception = new LexerException('Invalid ASCII control character', $position, $string);

                        yield $previous = new Token(T::INVALID, $start, $row, $char, $char, $exception);
                        break;
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
                    yield $previous = new Token(T::NAME | T::UNQUOTED_NAME, $start, $row, $value, null);
            }
        }
    }

    private function parseWhitespace(string $string, int &$position, int &$column, int &$row): string
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

    private function parseString(int $type, string $string, int &$position, int &$column, int &$row, string $quote, string $prefix = ''): Token
    {
        $startAt = $position - 1 - strlen($prefix);
        $length = strlen($string);
        $isString = ($type & T::STRING) !== 0;
        $isAtVariable = ($type & T::AT_VARIABLE) !== 0;
        $backslashes = $isString && !$this->settings->getMode()->containsAny(SqlMode::NO_BACKSLASH_ESCAPES);

        $orig = [$quote];
        $escaped = false;
        $finished = false;
        while ($position < $length) {
            $next = $string[$position];
            // todo: check for \0 in names?
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

        $orig = implode('', $orig);

        if (!$finished) {
            $exception = new LexerException("End of string not found. Starts with " . substr($string, $startAt - 1, 100), $position, $string);

            return new Token($type | T::INVALID, $startAt, $row, $prefix . $orig, $prefix . $orig, $exception);
        }

        // remove quotes
        $value = substr($orig, 1, -1);
        // unescape double quotes
        $value = str_replace($quote . $quote, $quote, $value);
        if ($backslashes) {
            // unescape backslashes only in string context
            $value = str_replace($this->escapeKeys, $this->escapeValues, $value);
        }

        return new Token($type, $startAt, $row, ($isAtVariable ? $prefix : '') . $value, $prefix . $orig);
    }

    private function parseNumber(string $string, int &$position, int &$column, int $row, string $start): ?Token
    {
        $startAt = $position - 1;
        $type = T::VALUE | T::NUMBER;
        $length = strlen($string);
        $offset = 0;
        $isNumeric = isset(self::$numbersKey[$start]);
        $base = $start;
        $minusAllowed = $start === '-';
        $exp = '';
        do {
            // integer (prefixed by any number of "-")
            $next = '';
            while ($position + $offset < $length) {
                $next = $string[$position + $offset];
                if (isset(self::$numbersKey[$next]) || ($minusAllowed && ($next === '-' || $next === ' '))) {
                    $base .= $next;
                    $offset++;
                    if ($next !== '-' && $next !== ' ') {
                        $isNumeric = true;
                        $minusAllowed = false;
                    }
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
                            $isNumeric = true;
                        } else {
                            break;
                        }
                    }
                } else {
                    break;
                }
            }
            if (!$isNumeric) {
                return null;
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
                                $len = strlen($base . $exp) - 1;
                                $position += $len;
                                $column += $len;
                                $exception = new LexerException('Invalid number exponent ' . $exp, $position, $string);

                                return new Token($type | T::INVALID, $startAt, $row, $base . $exp, $base . $exp, $exception);
                            }
                            break;
                        }
                    }
                    if (!$expComplete) {
                        if (strpos($base, '.') !== false) {
                            $len = strlen($base . $exp) - 1;
                            $position += $len;
                            $column += $len;
                            $exception = new LexerException('Invalid number exponent ' . $exp, $position, $string);

                            return new Token($type | T::INVALID, $startAt, $row, $base . $exp, $base . $exp, $exception);
                        } else {
                            return null;
                        }
                    }
                } elseif (isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                    $isNumeric = false;
                    break 2;
                }
            } while (false); // @phpstan-ignore-line
        } while (false); // @phpstan-ignore-line

        if (!$isNumeric) {
            return null;
        }

        $orig = $base . $exp;
        $value = $base . str_replace(['e+', ' '], ['e', ''], strtolower($exp));
        if (substr($orig, 0, 3) === '-- ') {
            return null;
        }

        $len = strlen($orig) - 1;
        $position += $len;
        $column += $len;

        if (ctype_digit($value)) {
            $type |= T::INT | T::UINT;

            return new Token($type, $startAt, $row, $value, $orig);
        }

        // value clean-up: --+.123E+2 => 0.123e2
        while ($value[0] === '-' && $value[1] === '-') {
            $value = substr($value, 2);
        }
        $value = ltrim($value, '+');
        if (strpos($value, '.') === strlen($value) - 1) {
            $value .= '0';
        }
        if ($value[0] === '.') {
            $value = '0' . $value;
        }
        $value = str_replace('.e', '.0e', $value);

        if (preg_match('~^(?:0|-?[1-9][0-9]*)$~', $value) !== 0) {
            $type |= TokenType::INT;
        }

        return new Token($type, $startAt, $row, $value, $orig);
    }

}
