<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable SlevomatCodingStandard.ControlStructures.JumpStatementsSpacing
// phpcs:disable SlevomatCodingStandard.ControlStructures.AssignmentInCondition
// phpcs:disable SlevomatCodingStandard.ControlStructures.NewWithParentheses.MissingParentheses
// phpcs:disable Generic.Formatting.DisallowMultipleStatements.SameLine

namespace SqlFtw\Parser;

use Generator;
use SqlFtw\Parser\TokenType as T;
use SqlFtw\Platform\ClientSideExtension;
use SqlFtw\Platform\Features\Feature;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlMode;
use function array_flip;
use function array_keys;
use function array_merge;
use function array_values;
use function ctype_alnum;
use function ctype_alpha;
use function ctype_digit;
use function end;
use function implode;
use function in_array;
use function ltrim;
use function ord;
use function preg_match;
use function str_replace;
use function strcasecmp;
use function strlen;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use function trim;

/**
 * SQL lexer - breaks input string into `Token` objects, resolves delimiters and returns `TokenList` objects
 */
class Lexer
{

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
    private static array $numbersKey = [];

    /** @var array<string|int, int> */
    private static array $hexadecKey;

    /** @var array<string|int, int> */
    private static array $nameCharsKey;

    /** @var array<string|int, int> */
    private static array $userVariableNameCharsKey;

    /** @var array<string, int> */
    private static array $operatorSymbolsKey;

    private ParserConfig $config;

    private Session $session;

    private Platform $platform;

    private bool $withComments;

    private bool $withWhitespace;

    /** @var list<string> */
    private array $escapeKeys;

    /** @var list<string> */
    private array $escapeValues;

    public function __construct(ParserConfig $config, Session $session)
    {
        if (self::$numbersKey === []) {
            self::$numbersKey = array_flip(self::NUMBERS); // @phpstan-ignore-line
            self::$hexadecKey = array_flip(array_merge(self::NUMBERS, ['A', 'a', 'B', 'b', 'C', 'c', 'D', 'd', 'E', 'e', 'F', 'f']));
            self::$nameCharsKey = array_flip(array_merge(self::LETTERS, self::NUMBERS, ['$', '_']));
            self::$userVariableNameCharsKey = array_flip(array_merge(self::LETTERS, self::NUMBERS, ['$', '_', '.']));
            self::$operatorSymbolsKey = array_flip(self::OPERATOR_SYMBOLS);
        }

        $this->config = $config;
        $this->session = $session;
        $this->platform = $config->getPlatform();
        $this->withComments = $config->tokenizeComments();
        $this->withWhitespace = $config->tokenizeWhitespace();

        $this->escapeKeys = array_keys(self::MYSQL_ESCAPES);
        $this->escapeValues = array_values(self::MYSQL_ESCAPES);
    }

    /**
     * Tokenize SQL code and return a generator of TokenList objects (terminated by DELIMITER or DELIMITER_DEFINITION tokens)
     * @return Generator<TokenList>
     */
    public function tokenize(string $string): Generator
    {
        // this allows TokenList to not have to call doAutoSkip() million times when there are no skippable tokens produced
        $autoSkip = ($this->withWhitespace ? T::WHITESPACE : 0) | ($this->withComments ? T::COMMENT : 0);

        $extensions = $this->config->getClientSideExtensions();
        $parseOldNullLiteral = isset($this->platform->features[Feature::OLD_NULL_LITERAL]);
        $parseOptimizerHints = isset($this->platform->features[Feature::OPTIMIZER_HINTS]);
        $allowDelimiterDefinition = ($extensions & ClientSideExtension::ALLOW_DELIMITER_DEFINITION) !== 0;

        // last significant token parsed (comments and whitespace are skipped here)
        $previous = $p = new Token; $p->type = TokenType::END; $p->position = 0; $p->row = 0; $p->value = '';

        // reset
        $tokens = [];
        $invalid = false;
        $condition = null;
        $hint = false;
        $delimiter = $this->session->getDelimiter();
        $commentDepth = 0;
        $position = 0;
        $row = 1;

        $length = strlen($string);
        continue_tokenizing:
        while ($position < $length) {
            $char = $string[$position];
            $start = $position;
            $position++;

            if ($char === $delimiter[0]) {
                if (substr($string, $position - 1, strlen($delimiter)) === $delimiter) {
                    $position += strlen($delimiter) - 1;
                    $tokens[] = $t = new Token; $t->type = T::DELIMITER; $t->position = $start; $t->row = $row; $t->value = $delimiter;
                    goto yield_token_list;
                }
            }

            switch ($char) {
                case ' ':
                case "\t":
                case "\r":
                case "\n":
                    $ws = $char;
                    if ($char === "\n") {
                        $row++;
                    }
                    while ($position < $length) {
                        $next = $string[$position];
                        if ($next === ' ' || $next === "\t" || $next === "\r") {
                            $ws .= $next;
                            $position++;
                        } elseif ($next === "\n") {
                            $ws .= $next;
                            $position++;
                            $row++;
                        } else {
                            break;
                        }
                    }
                    if ($this->withWhitespace) {
                        $tokens[] = $t = new Token; $t->type = T::WHITESPACE; $t->position = $start; $t->row = $row; $t->value = $ws;
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
                    $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL; $t->position = $start; $t->row = $row; $t->value = $char;
                    break;
                case ':':
                    if (($extensions & ClientSideExtension::ALLOW_NAMED_DOUBLE_COLON_PLACEHOLDERS) !== 0) {
                        $name = '';
                        while ($position < $length) {
                            $nextDc = $string[$position];
                            if ($nextDc === '_' || ctype_alpha($nextDc) || (strlen($name) > 0 && ctype_digit($nextDc))) {
                                $name .= $nextDc;
                                $position++;
                            } else {
                                break;
                            }
                        }
                        if ($name !== '') {
                            $tokens[] = $previous = $t = new Token; $t->type = T::PLACEHOLDER | T::DOUBLE_COLON_PLACEHOLDER; $t->position = $start; $t->row = $row; $t->value = ':' . $name;
                            break;
                        }
                    }
                    $operator = $char;
                    while ($position < $length) {
                        $next2 = $string[$position];
                        if (!isset($this->platform->operators[$operator . $next2])) {
                            if ($operator !== ':') {
                                $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->position = $start; $t->row = $row; $t->value = $operator;
                            } else {
                                $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL; $t->position = $start; $t->row = $row; $t->value = $char;
                            }
                            break 2;
                        }
                        if (isset(self::$operatorSymbolsKey[$next2])) {
                            $operator .= $next2;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    if ($operator !== ':') {
                        $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->position = $start; $t->row = $row; $t->value = $operator;
                    } else {
                        $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL; $t->position = $start; $t->row = $row; $t->value = $char;
                    }
                    break;
                case '*':
                    // /*!12345 ... */
                    if ($position < $length && $string[$position] === '/') {
                        if ($condition !== null) {
                            // end of optional comment
                            $afterComment = $string[$position + 1];
                            if ($this->withWhitespace && $afterComment !== ' ' && $afterComment !== "\t" && $afterComment !== "\n") {
                                // insert a space in case that optional comment is immediately followed by a non-whitespace token
                                // (resulting token list would serialize into invalid code)
                                $tokens[] = $t = new Token; $t->type = T::WHITESPACE; $t->position = $position + 1; $t->row = $row; $t->value = ' ';
                            }
                            $condition = null;
                            $position++;
                            break;
                        } elseif ($hint) {
                            // end of optimizer hint
                            $tokens[] = $t = new Token; $t->type = T::OPTIMIZER_HINT_END; $t->position = $position - 1; $t->row = $row; $t->value = '*/';

                            $hint = false;
                            $position++;
                            break;
                        }
                    }
                    // continue
                case '\\':
                    if ($parseOldNullLiteral && $char === '\\' && $position < $length && $string[$position] === 'N') {
                        $position++;
                        $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::VALUE; $t->position = $start; $t->row = $row; $t->value = '\\N';
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
                    $operator2 = $char;
                    while ($position < $length) {
                        $next3 = $string[$position];
                        if (!isset($this->platform->operators[$operator2 . $next3])) {
                            $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->position = $start; $t->row = $row; $t->value = $operator2;
                            break 2;
                        }
                        if (isset(self::$operatorSymbolsKey[$next3])) {
                            $operator2 .= $next3;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->position = $start; $t->row = $row; $t->value = $operator2;
                    break;
                case '?':
                    if (($extensions & ClientSideExtension::ALLOW_NUMBERED_QUESTION_MARK_PLACEHOLDERS) !== 0) {
                        $number = '';
                        while ($position < $length) {
                            $nextQm = $string[$position];
                            if (ctype_digit($nextQm)) {
                                $number .= $nextQm;
                                $position++;
                            } else {
                                break;
                            }
                        }
                        if ($number !== '') {
                            $tokens[] = $previous = $t = new Token; $t->type = T::PLACEHOLDER | T::NUMBERED_QUESTION_MARK_PLACEHOLDER; $t->position = $start; $t->row = $row; $t->value = '?' . $number;
                            break;
                        }
                    }
                    if ($position < $length && ctype_alnum($string[$position])) {
                        $exception = new LexerException("Invalid character after placeholder $string[$position].", $position, $string);

                        $tokens[] = $t = new Token; $t->type = T::PLACEHOLDER | T::QUESTION_MARK_PLACEHOLDER | T::INVALID; $t->position = $start; $t->row = $row; $t->value = '?'; $t->exception = $exception;
                        $invalid = true;
                        break;
                    }
                    if ($position > 1 && ctype_alnum($string[$position - 2])) {
                        $exception = new LexerException("Invalid character before placeholder {$string[$position - 2]}.", $position, $string);

                        $tokens[] = $t = new Token; $t->type = T::PLACEHOLDER | T::QUESTION_MARK_PLACEHOLDER | T::INVALID; $t->position = $start; $t->row = $row; $t->value = '?'; $t->exception = $exception;
                        $invalid = true;
                        break;
                    }

                    $tokens[] = $previous = $t = new Token; $t->type = T::PLACEHOLDER | T::QUESTION_MARK_PLACEHOLDER; $t->position = $start; $t->row = $row; $t->value = $char;
                    break;
                case '@':
                    $var = $char;
                    $second = $string[$position];
                    if ($second === '@') {
                        // @@variable
                        $var .= $second;
                        $position++;
                        if ($string[$position] === '`') {
                            // @@`variable`
                            $position++;
                            $tokens[] = $previous = $this->parseString(T::NAME | T::AT_VARIABLE | T::BACKTICK_QUOTED_STRING, $string, $position, $row, '`', '@@');
                            break;
                        }
                        while ($position < $length) {
                            $next4 = $string[$position];
                            if ($next4 === '@' || isset(self::$nameCharsKey[$next4]) || ord($next4) > 127) {
                                $var .= $next4;
                                $position++;
                            } else {
                                break;
                            }
                        }

                        $yieldDelimiter = false;
                        if (substr($var, -strlen($delimiter)) === $delimiter) { // str_ends_with()
                            // fucking name-like delimiter after name without whitespace
                            $var = substr($var, 0, -strlen($delimiter));
                            $yieldDelimiter = true;
                        }
                        if (strcasecmp(substr($var, 2), 'DEFAULT') === 0) {
                            // todo: probably all magic functions?
                            $exception = new LexerException("Invalid variable name $var.", $position, $string);

                            $tokens[] = $t = new Token; $t->type = T::NAME | T::AT_VARIABLE | T::INVALID; $t->position = $start; $t->row = $row; $t->value = $var; $t->exception = $exception;
                            $invalid = true;
                            break;
                        }

                        $tokens[] = $previous = $t = new Token; $t->type = T::NAME | T::AT_VARIABLE; $t->position = $start; $t->row = $row; $t->value = $var;

                        if ($yieldDelimiter) {
                            $tokens[] = $t = new Token; $t->type = T::DELIMITER; $t->position = $start; $t->row = $row; $t->value = $delimiter;
                            goto yield_token_list;
                        }
                    } elseif ($second === '`') {
                        $position++;
                        $tokens[] = $previous = $this->parseString(T::NAME | T::AT_VARIABLE | T::BACKTICK_QUOTED_STRING, $string, $position, $row, $second, '@');
                    } elseif ($second === "'") {
                        $position++;
                        $tokens[] = $previous = $this->parseString(T::NAME | T::AT_VARIABLE | T::SINGLE_QUOTED_STRING, $string, $position, $row, $second, '@');
                    } elseif ($second === '"') {
                        $position++;
                        $tokens[] = $previous = $this->parseString(T::NAME | T::AT_VARIABLE | T::DOUBLE_QUOTED_STRING, $string, $position, $row, $second, '@');
                    } elseif (isset(self::$userVariableNameCharsKey[$second]) || ord($second) > 127) {
                        // @variable
                        $var .= $second;
                        $position++;
                        while ($position < $length) {
                            $next5 = $string[$position];
                            if (isset(self::$userVariableNameCharsKey[$next5]) || ord($next5) > 127) {
                                $var .= $next5;
                                $position++;
                            } else {
                                break;
                            }
                        }

                        $yieldDelimiter = false;
                        if (substr($var, -strlen($delimiter)) === $delimiter) { // str_ends_with()
                            // fucking name-like delimiter after name without whitespace
                            $var = substr($var, 0, -strlen($delimiter));
                            $yieldDelimiter = true;
                        }
                        if (strcasecmp(substr($var, 1), 'DEFAULT') === 0) {
                            // todo: probably all magic functions?
                            $exception = new LexerException("Invalid variable name $var.", $position, $string);

                            $tokens[] = $t = new Token; $t->type = T::NAME | T::AT_VARIABLE | T::INVALID; $t->position = $start; $t->row = $row; $t->value = $var; $t->exception = $exception;
                            $invalid = true;
                            break;
                        }

                        $tokens[] = $previous = $t = new Token; $t->type = T::NAME | T::AT_VARIABLE; $t->position = $start; $t->row = $row; $t->value = $var;

                        if ($yieldDelimiter) {
                            $tokens[] = $t = new Token; $t->type = T::DELIMITER; $t->position = $start; $t->row = $row; $t->value = $delimiter;
                            goto yield_token_list;
                        }
                    } else {
                        // simple @ (valid as empty host name)
                        $tokens[] = $previous = $t = new Token; $t->type = T::NAME | T::AT_VARIABLE; $t->position = $start; $t->row = $row; $t->value = $var;
                        break;
                    }
                    break;
                case '#':
                    // # comment
                    $hashComment = $char;
                    while ($position < $length) {
                        $next6 = $string[$position];
                        $hashComment .= $next6;
                        $position++;
                        if ($next6 === "\n") {
                            $row++;
                            break;
                        }
                    }
                    if ($this->withComments) {
                        $tokens[] = $previous = $t = new Token; $t->type = T::COMMENT | T::HASH_COMMENT; $t->position = $start; $t->row = $row; $t->value = $hashComment;
                    }
                    break;
                case '/':
                    $next7 = $position < $length ? $string[$position] : '';
                    if ($next7 === '/') {
                        // // comment
                        $position++;
                        $slashComment = $char . $next7;
                        while ($position < $length) {
                            $next7 = $string[$position];
                            $slashComment .= $next7;
                            $position++;
                            if ($next7 === "\n") {
                                $row++;
                                break;
                            }
                        }
                        if ($this->withComments) {
                            $tokens[] = $previous = $t = new Token; $t->type = T::COMMENT | T::DOUBLE_SLASH_COMMENT; $t->position = $start; $t->row = $row; $t->value = $slashComment;
                        }
                    } elseif ($next7 === '*') {
                        $position++;

                        $optional = $string[$position] === '!';
                        $beforeComment = $string[$position - 3];
                        // todo: Maria
                        $validOptional = true;
                        if ($optional) {
                            if (strlen($string) > $position + 1 && $string[$position + 1] === '*' && $string[$position + 2] === '/') {
                                // /*!*/
                                $position += 3;
                                break;
                            }
                            $validOptional = preg_match('~^([Mm]?!(?:00000|[1-9]\d{4,5})?)\D~', substr($string, $position, 10), $m) === 1;
                            if ($validOptional) {
                                $versionId = strtoupper(str_replace('!', '', $m[1]));
                                if ($this->platform->interpretOptionalComment($versionId)) {
                                    if ($this->withWhitespace && $beforeComment !== ' ' && $beforeComment !== "\t" && $beforeComment !== "\n") {
                                        // insert a space in case that optional comment was immediately following a non-whitespace token
                                        // (resulting token list would serialize into invalid code)
                                        $tokens[] = $t = new Token; $t->type = T::WHITESPACE; $t->position = $position - 3; $t->row = $row; $t->value = ' ';
                                    }
                                    $condition = $versionId;
                                    $position += strlen($versionId) + 1;

                                    // continue parsing as conditional code
                                    break;
                                }
                            }
                        }

                        $isHint = $string[$position] === '+';
                        if ($isHint && $parseOptimizerHints) {
                            $optimizerHintCanFollow = ($previous->type & TokenType::RESERVED) !== 0
                                && in_array(strtoupper($previous->value), [Keyword::SELECT, Keyword::INSERT, Keyword::REPLACE, Keyword::UPDATE, Keyword::DELETE], true);

                            if ($optimizerHintCanFollow) {
                                $hint = true;
                                $position++;
                                $tokens[] = $t = new Token; $t->type = T::OPTIMIZER_HINT_START; $t->position = $start; $t->row = $row; $t->value = '/*+';
                                break;
                            }
                        }

                        // parse as a regular comment
                        $commentDepth++;
                        $comment = $char . $next7;
                        $terminated = false;
                        while ($position < $length) {
                            $next8 = $string[$position];
                            if ($next8 === '/' && ($position + 1 < $length) && $string[$position + 1] === '*') {
                                $comment .= $next8 . $string[$position + 1];
                                $position += 2;
                                $commentDepth++;
                            } elseif ($next8 === '*' && ($position + 1 < $length) && $string[$position + 1] === '/') {
                                $comment .= $next8 . $string[$position + 1];
                                $position += 2;
                                $commentDepth--;
                                if ($commentDepth === 0) {
                                    $terminated = true;
                                    break;
                                }
                            } elseif ($next8 === "\n") {
                                $comment .= $next8;
                                $position++;
                                $row++;
                            } else {
                                $comment .= $next8;
                                $position++;
                            }
                        }
                        if (!$terminated) {
                            $exception = new LexerException('End of comment not found.', $position, $string);

                            $tokens[] = $t = new Token; $t->type = T::COMMENT | T::BLOCK_COMMENT | T::INVALID; $t->position = $start; $t->row = $row; $t->value = $comment; $t->exception = $exception;
                            $invalid = true;
                            break;
                        } elseif (!$validOptional) {
                            $condition = null;
                            $exception = new LexerException('Invalid optional comment: ' . $comment, $position, $string);

                            $tokens[] = $t = new Token; $t->type = T::COMMENT | T::BLOCK_COMMENT | T::OPTIONAL_COMMENT | T::INVALID; $t->position = $start; $t->row = $row; $t->value = $comment; $t->exception = $exception;
                            $invalid = true;
                            break;
                        }

                        if ($this->withComments) {
                            if ($optional) {
                                // /*!12345 comment (when not interpreted as code) */
                                $tokens[] = $t = new Token; $t->type = T::COMMENT | T::BLOCK_COMMENT | T::OPTIONAL_COMMENT; $t->position = $start; $t->row = $row; $t->value = $comment;
                            } elseif ($hint) {
                                // /*+ comment */ (when not interpreted as code)
                                $tokens[] = $t = new Token; $t->type = T::COMMENT | T::BLOCK_COMMENT | T::OPTIMIZER_HINT_COMMENT; $t->position = $start; $t->row = $row; $t->value = $comment;
                            } else {
                                // /* comment */
                                $tokens[] = $t = new Token; $t->type = T::COMMENT | T::BLOCK_COMMENT; $t->position = $start; $t->row = $row; $t->value = $comment;
                            }
                        }
                    } else {
                        $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->position = $start; $t->row = $row; $t->value = $char;
                    }
                    break;
                case '"':
                    $type = $this->session->getMode()->containsAny(SqlMode::ANSI_QUOTES)
                        ? T::NAME | T::DOUBLE_QUOTED_STRING
                        : T::VALUE | T::STRING | T::DOUBLE_QUOTED_STRING;

                    $tokens[] = $previous = $this->parseString($type, $string, $position, $row, '"');
                    break;
                case "'":
                    $tokens[] = $previous = $this->parseString(T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, $string, $position, $row, "'");
                    break;
                case '`':
                    $tokens[] = $previous = $this->parseString(T::NAME | T::BACKTICK_QUOTED_STRING, $string, $position, $row, '`');
                    break;
                case '.':
                    $next9 = $position < $length ? $string[$position] : '';
                    // .123 cannot follow a name, e.g.: "select 1ea10.1a20, ...", but can follow a keyword, e.g.: "INTERVAL .4 SECOND"
                    if (isset(self::$numbersKey[$next9]) && (($previous->type & T::NAME) === 0 || ($previous->type & T::KEYWORD) !== 0)) {
                        $token = $this->parseNumber($string, $position, $row, '.');
                        if ($token !== null) {
                            $tokens[] = $previous = $token;
                            break;
                        }
                    }
                    $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL; $t->position = $start; $t->row = $row; $t->value = $char;
                    break;
                case '-':
                    $second = $position < $length ? $string[$position] : '';
                    $numberCanFollow = ($previous->type & T::END) !== 0
                        || (($previous->type & T::SYMBOL) !== 0 && $previous->value !== ')' && $previous->value !== '?')
                        || (($previous->type & T::KEYWORD) !== 0 && strcasecmp($previous->value, Keyword::DEFAULT) === 0);
                    if ($numberCanFollow) {
                        $token = $this->parseNumber($string, $position, $row, '-');
                        if ($token !== null) {
                            $tokens[] = $previous = $token;
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
                            $line = substr($string, $position - 1, $endOfLine - $position + 2);
                            $position += strlen($line) - 1;
                            $row++;

                            if ($this->withComments) {
                                $tokens[] = $previous = $t = new Token; $t->type = T::COMMENT | T::DOUBLE_HYPHEN_COMMENT; $t->position = $start; $t->row = $row; $t->value = $line;
                            }
                            break;
                        }

                        $tokens[] = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->position = $start; $t->row = $row; $t->value = '-';
                        $position++;

                        $token = $this->parseNumber($string, $position, $row, '-');
                        if ($token !== null) {
                            $tokens[] = $previous = $token;
                            break;
                        }
                    }

                    $operator3 = $char;
                    while ($position < $length) {
                        $next10 = $string[$position];
                        if (!isset($this->platform->operators[$operator3 . $next10])) {
                            $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->position = $start; $t->row = $row; $t->value = $operator3;
                            break 2;
                        }
                        if (isset(self::$operatorSymbolsKey[$next10])) {
                            $operator3 .= $next10;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->position = $start; $t->row = $row; $t->value = $operator3;
                    break;
                case '+':
                    $next11 = $position < $length ? $string[$position] : '';
                    $numberCanFollow = ($previous->type & T::END) !== 0
                        || (($previous->type & T::SYMBOL) !== 0 && $previous->value !== ')' && $previous->value !== '?')
                        || (($previous->type & T::KEYWORD) !== 0 && $previous->value === Keyword::DEFAULT);
                    if ($numberCanFollow && ($next11 === '.' || isset(self::$numbersKey[$next11]))) {
                        $token = $this->parseNumber($string, $position, $row, '+');
                        if ($token !== null) {
                            $tokens[] = $previous = $token;
                            break;
                        }
                    }

                    $operator4 = $char;
                    while ($position < $length) {
                        $next12 = $string[$position];
                        if (!isset($this->platform->operators[$operator4 . $next12])) {
                            $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->position = $start; $t->row = $row; $t->value = $operator4;
                            break 2;
                        }
                        if (isset(self::$operatorSymbolsKey[$next12])) {
                            $operator4 .= $next12;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->position = $start; $t->row = $row; $t->value = $operator4;
                    break;
                case '0':
                    $next13 = $position < $length ? $string[$position] : '';
                    if ($next13 === 'b') {
                        // 0b00100011
                        $position++;
                        $bits = '';
                        while ($position < $length) {
                            $next13 = $string[$position];
                            if ($next13 === '0' || $next13 === '1') {
                                $bits .= $next13;
                                $position++;
                            } elseif (isset(self::$nameCharsKey[$next13])) {
                                // name pretending to be a binary literal :E
                                $position -= strlen($bits) + 1;
                                break;
                            } else {
                                $orig = $char . 'b' . $bits;
                                $tokens[] = $previous = $t = new Token; $t->type = T::VALUE | T::BINARY_LITERAL; $t->position = $start; $t->row = $row; $t->value = $bits; $t->original = $orig;
                                break 2;
                            }
                        }
                    } elseif ($next13 === 'x') {
                        // 0x001f
                        $position++;
                        $bits = '';
                        while ($position < $length) {
                            $next13 = $string[$position];
                            if (isset(self::$hexadecKey[$next13])) {
                                $bits .= $next13;
                                $position++;
                            } elseif (isset(self::$nameCharsKey[$next13])) {
                                // name pretending to be a hexadecimal literal :E
                                $position -= strlen($bits) + 1;
                                break;
                            } else {
                                $orig = $char . 'x' . $bits;
                                $tokens[] = $previous = $t = new Token; $t->type = T::VALUE | T::HEXADECIMAL_LITERAL; $t->position = $start; $t->row = $row; $t->value = strtolower($bits); $t->original = $orig;
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
                    $uuid = substr($string, $position - 1, 36);
                    // UUID
                    if (strlen($uuid) === 36 && preg_match(self::UUID_REGEXP, $uuid) !== 0) {
                        $position += 35;
                        $tokens[] = $previous = $t = new Token; $t->type = T::VALUE | T::UUID; $t->position = $start; $t->row = $row; $t->value = $uuid;
                        break;
                    }
                    // IPv4
                    if (preg_match(self::IP_V4_REGEXP, $uuid, $m) !== 0) {
                        $ipv4 = $m[0]; // @phpstan-ignore offsetAccess.notFound
                        $position += strlen($ipv4) - 1;
                        $tokens[] = $previous = $t = new Token; $t->type = T::VALUE | T::STRING; $t->position = $start; $t->row = $row; $t->value = $ipv4;
                        break;
                    }
                    $token = $this->parseNumber($string, $position, $row, $char);
                    if ($token !== null) {
                        $tokens[] = $previous = $token;
                        break;
                    }
                    // continue
                case 'B':
                case 'b':
                    // b'01'
                    // B'01'
                    if (($char === 'B' || $char === 'b') && $position < $length && $string[$position] === '\'') {
                        $position++;
                        $bits = $next14 = '';
                        while ($position < $length) {
                            $next14 = $string[$position];
                            if ($next14 === '\'') {
                                $position++;
                                break;
                            } else {
                                $bits .= $next14;
                                $position++;
                            }
                        }
                        if (ltrim($bits, '01') === '') {
                            $orig = $char . '\'' . $bits . '\'';

                            $tokens[] = $previous = $t = new Token; $t->type = T::VALUE | T::BINARY_LITERAL; $t->position = $start; $t->row = $row; $t->value = $bits; $t->original = $orig;
                        } else {
                            $exception = new LexerException('Invalid binary literal', $position, $string);
                            $orig = $char . '\'' . $bits . $next14;

                            $tokens[] = $previous = $t = new Token; $t->type = T::VALUE | T::BINARY_LITERAL | T::INVALID; $t->position = $start; $t->row = $row; $t->value = $orig; $t->original = $orig; $t->exception = $exception; // todo why orig?
                            $invalid = true;
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
                    $uuid2 = substr($string, $position - 1, 36);
                    // UUID
                    if (strlen($uuid2) === 36 && preg_match(self::UUID_REGEXP, $uuid2) !== 0) {
                        $position += 35;
                        $tokens[] = $previous = $t = new Token; $t->type = T::VALUE | T::UUID; $t->position = $start; $t->row = $row; $t->value = $uuid2;
                        break;
                    }
                    // continue
                case 'X':
                case 'x':
                    if (($char === 'X' || $char === 'x') && $position < $length && $string[$position] === '\'') {
                        $position++;
                        $bits = $next15 = '';
                        while ($position < $length) {
                            $next15 = $string[$position];
                            if ($next15 === '\'') {
                                $position++;
                                break;
                            } else {
                                $bits .= $next15;
                                $position++;
                            }
                        }
                        $bits = strtolower($bits);
                        if (ltrim($bits, '0123456789abcdef') === '') {
                            $orig = $char . '\'' . $bits . '\'';

                            $tokens[] = $previous = $t = new Token; $t->type = T::VALUE | T::HEXADECIMAL_LITERAL; $t->position = $start; $t->row = $row; $t->value = $bits; $t->original = $orig;
                        } else {
                            $exception = new LexerException('Invalid hexadecimal literal', $position, $string);
                            $orig = $char . '\'' . $bits . $next15;

                            $tokens[] = $previous = $t = new Token; $t->type = T::VALUE | T::HEXADECIMAL_LITERAL | T::INVALID; $t->position = $start; $t->row = $row; $t->value = $orig; $t->original = $orig; $t->exception = $exception; // todo why orig?
                            $invalid = true;
                            break;
                        }
                        break;
                    }
                    // continue
                case 'N':
                    $next16 = $position < $length ? $string[$position] : null;
                    if ($char === 'N' && $next16 === '"') {
                        $position++;
                        $type = $this->session->getMode()->containsAny(SqlMode::ANSI_QUOTES)
                            ? T::NAME | T::DOUBLE_QUOTED_STRING
                            : T::VALUE | T::STRING | T::DOUBLE_QUOTED_STRING;

                        $tokens[] = $previous = $this->parseString($type, $string, $position, $row, '"', 'N');
                        break;
                    } elseif ($char === 'N' && $next16 === "'") {
                        $position++;
                        $tokens[] = $previous = $this->parseString(T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, $string, $position, $row, "'", 'N');
                        break;
                    } elseif ($char === 'N' && $next16 === '`') {
                        $position++;
                        $tokens[] = $previous = $this->parseString(T::NAME | T::BACKTICK_QUOTED_STRING, $string, $position, $row, "`", 'N');
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
                    $name = $char;
                    while ($position < $length) {
                        $next17 = $string[$position];
                        if (isset(self::$nameCharsKey[$next17]) || ord($next17) > 127) {
                            $name .= $next17;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    $yieldDelimiter = false;
                    if (substr($name, -strlen($delimiter)) === $delimiter) { // str_ends_with()
                        // fucking name-like delimiter after name without whitespace
                        $name = substr($name, 0, -strlen($delimiter));
                        $yieldDelimiter = true;
                    }

                    $upper = strtoupper($name);
                    if (isset($this->platform->reserved[$upper])) {
                        if (isset($this->platform->operators[$upper])) {
                            $tokens[] = $previous = $t = new Token; $t->type = T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME | T::OPERATOR; $t->position = $start; $t->row = $row; $t->value = $name;
                        } else {
                            $tokens[] = $previous = $t = new Token; $t->type = T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME; $t->position = $start; $t->row = $row; $t->value = $name;
                        }
                    } elseif (isset($this->platform->nonReserved[$upper])) {
                        $tokens[] = $previous = $t = new Token; $t->type = T::KEYWORD | T::NAME | T::UNQUOTED_NAME; $t->position = $start; $t->row = $row; $t->value = $name;
                    } elseif ($upper === Keyword::DELIMITER && $allowDelimiterDefinition) {
                        $tokens[] = $t = new Token; $t->type = T::KEYWORD | T::NAME | T::UNQUOTED_NAME; $t->position = $start; $t->row = $row; $t->value = $name;
                        $start = $position;
                        $whitespace = $this->parseWhitespace($string, $position, $row);
                        if ($this->withWhitespace) {
                            $tokens[] = $t = new Token; $t->type = T::WHITESPACE; $t->position = $start; $t->row = $row; $t->value = $whitespace;
                        }
                        $start = $position;
                        $del = '';
                        while ($position < $length) {
                            $next18 = $string[$position];
                            if ($next18 === "\n" || $next18 === "\r" || $next18 === "\t" || $next18 === ' ') {
                                break;
                            } else {
                                $del .= $next18;
                                $position++;
                            }
                        }
                        if ($del === '') {
                            $exception = new LexerException('Delimiter not found', $position, $string);

                            $tokens[] = $previous = $t = new Token; $t->type = T::INVALID; $t->position = $start; $t->row = $row; $t->value = $del; $t->exception = $exception;
                            $invalid = true;
                            break;
                        }
                        if (isset($this->platform->reserved[strtoupper($del)])) {
                            $exception = new LexerException('Delimiter can not be a reserved word', $position, $string);

                            $tokens[] = $previous = $t = new Token; $t->type = T::DELIMITER_DEFINITION | T::INVALID; $t->position = $start; $t->row = $row; $t->value = $del; $t->exception = $exception;
                            $invalid = true;
                            break;
                        }
                        // todo: quoted delimiters :E
                        /*
                         * The delimiter string can be specified as an unquoted or quoted argument on the delimiter command line.
                         * Quoting can be done with either single quote ('), double quote ("), or backtick (`) characters.
                         * To include a quote within a quoted string, either quote the string with a different quote character
                         * or escape the quote with a backslash (\) character. Backslash should be avoided outside quoted
                         * strings because it is the escape character for MySQL. For an unquoted argument, the delimiter is read
                         * up to the first space or end of line. For a quoted argument, the delimiter is read up to the matching quote on the line.
                         */
                        $delimiter = $del;
                        $this->session->setDelimiter($delimiter);
                        $tokens[] = $previous = $t = new Token; $t->type = T::DELIMITER_DEFINITION; $t->position = $start; $t->row = $row; $t->value = $delimiter;
                    } else {
                        $tokens[] = $previous = $t = new Token; $t->type = T::NAME | T::UNQUOTED_NAME; $t->position = $start; $t->row = $row; $t->value = $name;
                    }
                    if ($yieldDelimiter) {
                        $tokens[] = $t = new Token; $t->type = T::DELIMITER; $t->position = $start; $t->row = $row; $t->value = $delimiter;
                        goto yield_token_list;
                    } elseif (($previous->type & T::DELIMITER_DEFINITION) !== 0) {
                        goto yield_token_list;
                    }
                    break;
                default:
                    if (ord($char) < 32) {
                        $exception = new LexerException('Invalid ASCII control character', $position, $string);

                        $tokens[] = $previous = $t = new Token; $t->type = T::INVALID; $t->position = $start; $t->row = $row; $t->value = $char; $t->exception = $exception;
                        $invalid = true;
                        break;
                    }
                    $name2 = $char;
                    while ($position < $length) {
                        $next19 = $string[$position];
                        if (isset(self::$nameCharsKey[$next19]) || ord($next19) > 127) {
                            $name2 .= $next19;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    $tokens[] = $previous = $t = new Token; $t->type = T::NAME | T::UNQUOTED_NAME; $t->position = $start; $t->row = $row; $t->value = $name2;
            }
        }

        yield_token_list:
        if ($tokens !== []) {
            if ($condition !== null) {
                $lastToken = end($tokens);
                $condition = null;
                $exception = new LexerException("End of optional comment not found.", $lastToken->position, '');
                $tokens[] = $t = new Token; $t->type = T::END + T::INVALID; $t->position = 0; $t->row = 0; $t->value = ''; $t->exception = $exception;
                $invalid = true;
            }
            if ($hint) {
                $lastToken = end($tokens);
                $hint = false;
                $exception = new LexerException("End of optimizer hint not found.", $lastToken->position, '');
                $tokens[] = $t = new Token; $t->type = T::END + T::INVALID; $t->position = 0; $t->row = 0; $t->value = ''; $t->exception = $exception;
                $invalid = true;
            }

            yield new TokenList($tokens, $this->platform, $this->session, $autoSkip, $invalid);

            $tokens = [];
            $invalid = false;
        }

        if ($position < $length) {
            goto continue_tokenizing;
        }
    }

    private function parseWhitespace(string $string, int &$position, int &$row): string
    {
        $length = strlen($string);
        $whitespace = '';
        while ($position < $length) {
            $next = $string[$position];
            if ($next === ' ' || $next === "\t" || $next === "\r") {
                $whitespace .= $next;
                $position++;
            } elseif ($next === "\n") {
                $whitespace .= $next;
                $position++;
                $row++;
            } else {
                break;
            }
        }

        return $whitespace;
    }

    private function parseString(int $tokenType, string $string, int &$position, int &$row, string $quote, string $prefix = ''): Token
    {
        $startAt = $position - 1 - strlen($prefix);
        $length = strlen($string);

        $mode = $this->session->getMode();
        $ansi = $mode->containsAny(SqlMode::ANSI_QUOTES);
        $isAtVariable = ($tokenType & T::AT_VARIABLE) !== 0;
        $mayHaveBackslashes = ($tokenType & (T::STRING | T::SINGLE_QUOTED_STRING)) !== 0 || (!$ansi && ($tokenType & T::DOUBLE_QUOTED_STRING) !== 0);
        $backslashes = $mayHaveBackslashes && !$mode->containsAny(SqlMode::NO_BACKSLASH_ESCAPES);

        $orig = [$quote];
        $escaped = false;
        $finished = false;
        while ($position < $length) {
            $next = $string[$position];
            // todo: check for \0 in names?
            if ($next === $quote) {
                $orig[] = $next;
                $position++;
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
                $row++;
            } elseif ($backslashes && $next === '\\') {
                $escaped = !$escaped;
                $orig[] = $next;
                $position++;
            } elseif ($escaped && $next !== '\\' && $next !== $quote) {
                $escaped = false;
                $orig[] = $next;
                $position++;
            } else {
                $orig[] = $next;
                $position++;
            }
        }

        $orig = implode('', $orig);

        if (!$finished) {
            $exception = new LexerException("End of string not found. Starts with " . substr($string, $startAt - 1, 100), $position, $string);

            $t = new Token; $t->type = $tokenType | T::INVALID; $t->position = $startAt; $t->row = $row; $t->value = $prefix . $orig; $t->original = $prefix . $orig; $t->exception = $exception; // todo why orig?

            return $t;
        }

        // remove quotes
        $value = substr($orig, 1, -1);
        // unescape double quotes
        $value = str_replace($quote . $quote, $quote, $value);
        if ($backslashes) {
            // unescape backslashes only in string context
            $value = str_replace($this->escapeKeys, $this->escapeValues, $value);
        }

        $t = new Token; $t->type = $tokenType; $t->position = $startAt; $t->row = $row; $t->value = ($isAtVariable ? $prefix : '') . $value; $t->original = $prefix . $orig;

        return $t;
    }

    private function parseNumber(string $string, int &$position, int $row, string $start): ?Token
    {
        $startAt = $position - 1;
        $type = T::VALUE | T::NUMBER;
        $length = strlen($string);
        $offset = 0;
        $isFloat = $start === '.';
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
                $isFloat = true;
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
                                $exception = new LexerException('Invalid number exponent ' . $exp, $position, $string);

                                $t = new Token; $t->type = $type | T::INVALID; $t->position = $startAt; $t->row = $row; $t->value = $base . $exp; $t->original = $base . $exp; $t->exception = $exception;

                                return $t;
                            }
                            break;
                        }
                    }
                    if (!$expComplete) {
                        if (strpos($base, '.') !== false) {
                            $len = strlen($base . $exp) - 1;
                            $position += $len;
                            $exception = new LexerException('Invalid number exponent ' . $exp, $position, $string);

                            $t = new Token; $t->type = $type | T::INVALID; $t->position = $startAt; $t->row = $row; $t->value = $base . $exp; $t->original = $base . $exp; $t->exception = $exception; // todo why orig?

                            return $t;
                        } else {
                            return null;
                        }
                    }
                } elseif (isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                    if (!$isFloat) {
                        $isNumeric = false;
                    }
                    break 2;
                }
            } while (false); // @phpstan-ignore-line
        } while (false); // @phpstan-ignore-line

        if (!$isNumeric) {
            return null;
        }

        $orig = $base . $exp;
        $value = $base . str_replace(' ', '', strtolower($exp));
        if (strpos($orig, '-- ') === 0) {
            return null;
        }

        $len = strlen($orig) - 1;
        $position += $len;

        // todo: is "+42" considered uint?
        if (ctype_digit($value)) {
            $type |= T::INT | T::UINT;

            $t = new Token; $t->type = $type; $t->position = $startAt; $t->row = $row; $t->value = $value; $t->original = $orig;

            return $t;
        }

        // value clean-up: --+.123E+2 => +0.123e+2
        while ($value[0] === '-' && $value[1] === '-') {
            $value = substr($value, 2);
        }

        if (preg_match('~^(?:0|[+-]?[1-9]\\d*)$~', $value) !== 0) {
            $type |= TokenType::INT;
        }

        $t = new Token; $t->type = $type; $t->position = $startAt; $t->row = $row; $t->value = $value; $t->original = $orig;

        return $t;
    }

}
