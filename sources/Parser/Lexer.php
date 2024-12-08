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
// phpcs:disable Squiz.WhiteSpace.MemberVarSpacing.AfterComment

namespace SqlFtw\Parser;

use Generator;
use SqlFtw\Error\Error;
use SqlFtw\Parser\TokenType as T;
use SqlFtw\Platform\ClientSideExtension;
use SqlFtw\Platform\Features\Feature;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlMode;
use SqlFtw\Sql\Symbol;
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
use function preg_match;
use function str_replace;
use function strcasecmp;
use function strlen;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use const PREG_UNMATCHED_AS_NULL;

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

    public const ANCHORED_NUMBER_REGEXP = '~\G([+-]*)(\d*\.\d+|\d+\.?)(?:([eE])([+-]?)(\d*))?~';
    public const ANCHORED_UUID_REGEXP = '~\G[\dA-F]{8}-[\dA-F]{4}-[\dA-F]{4}-[\dA-F]{4}-[\dA-F]{12}~i';
    public const ANCHORED_IP_V4_REGEXP = '~\G((?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d))~';
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

    /** @var list<string> */
    private static array $escapeKeys;

    /** @var list<string> */
    private static array $escapeValues;

    private string $delimiter;

    private SqlMode $sqlMode;

    // config ----------------------------------------------------------------------------------------------------------

    private ParserConfig $config;

    private Session $session;

    private Platform $platform;

    private bool $withComments;

    private bool $withWhitespace;

    public function __construct(ParserConfig $config, Session $session)
    {
        if (self::$numbersKey === []) {
            self::$numbersKey = array_flip(self::NUMBERS); // @phpstan-ignore-line
            self::$hexadecKey = array_flip(array_merge(self::NUMBERS, ['A', 'a', 'B', 'b', 'C', 'c', 'D', 'd', 'E', 'e', 'F', 'f']));
            self::$nameCharsKey = array_flip(array_merge(self::LETTERS, self::NUMBERS, ['$', '_']/*, self::NON_ASCII_CHARS*/));
            self::$userVariableNameCharsKey = array_flip(array_merge(self::LETTERS, self::NUMBERS, ['$', '_', '.']/*, self::NON_ASCII_CHARS*/));
            self::$operatorSymbolsKey = array_flip(self::OPERATOR_SYMBOLS);
            self::$escapeKeys = array_keys(self::MYSQL_ESCAPES);
            self::$escapeValues = array_values(self::MYSQL_ESCAPES);
        }

        $this->delimiter = $session->getDelimiter();
        $session->onDelimiterChange(function (string $delimiter): void {
            $this->delimiter = $delimiter;
        });
        $this->sqlMode = $session->getMode();
        $session->onSqlModeChange(function (SqlMode $sqlMode): void {
            $this->sqlMode = $sqlMode;
        });

        $this->config = $config;
        $this->session = $session;
        $this->platform = $config->getPlatform();
        $this->withComments = $config->tokenizeComments();
        $this->withWhitespace = $config->tokenizeWhitespace();
    }

    /**
     * Tokenize SQL code and return a generator of TokenList objects (terminated by DELIMITER or DELIMITER_DEFINITION tokens)
     * @return Generator<TokenList>
     */
    public function tokenize(string $source): Generator
    {
        // this allows TokenList to not have to call doAutoSkip() million times when there are no skippable tokens produced
        $autoSkip = ($this->withWhitespace ? T::WHITESPACE : 0) | ($this->withComments ? T::COMMENT : 0);

        $extensions = $this->config->getClientSideExtensions();
        $parseOldNullLiteral = isset($this->platform->features[Feature::DEPRECATED_OLD_NULL_LITERAL]);
        $parseOptimizerHints = isset($this->platform->features[Feature::OPTIMIZER_HINTS]);
        $allowDelimiterDefinition = ($extensions & ClientSideExtension::ALLOW_DELIMITER_DEFINITION) !== 0;

        // last significant token parsed (comments and whitespace are skipped here)
        $previous = $p = new Token; $p->type = TokenType::END; $p->start = 0; $p->value = '';

        // reset
        $tokens = [];
        $invalid = false;
        $condition = null;
        $hint = false;
        $commentDepth = 0;
        $position = 0;

        $length = strlen($source);
        continue_tokenizing:
        while ($position < $length) {
            $char = $source[$position];
            $start = $position;
            $position++;

            if ($char === $this->delimiter[0]) {
                if (substr($source, $position - 1, strlen($this->delimiter)) === $this->delimiter) {
                    $position += strlen($this->delimiter) - 1;
                    $tokens[] = $t = new Token; $t->type = T::DELIMITER; $t->start = $start; $t->value = $this->delimiter;
                    goto yield_token_list;
                }
            }

            switch ($char) {
                case "\x00":
                case "\x01":
                case "\x02":
                case "\x03":
                case "\x04":
                case "\x05":
                case "\x06":
                case "\x07":
                case "\x08":
                //case "\x09": TAB
                //case "\x0A": LF
                case "\x0B":
                case "\x0C":
                //case "\x0D": CR
                case "\x0E":
                case "\x0F":
                case "\x10":
                case "\x11":
                case "\x12":
                case "\x13":
                case "\x14":
                case "\x15":
                case "\x16":
                case "\x17":
                case "\x18":
                case "\x19":
                case "\x1A":
                case "\x1B":
                case "\x1C":
                case "\x1E":
                case "\x1F":
                case "\x7F":
                    $exception = new LexerException('Invalid ASCII control character', $position, $source);

                    $tokens[] = $previous = $t = new Token; $t->type = T::INVALID; $t->start = $start; $t->value = $char; $t->exception = $exception;
                    $invalid = true;
                    break;
                case "\t":
                case "\r":
                case "\n":
                case ' ':
                    $ws = $char;
                    while ($position < $length) {
                        $next = $source[$position];
                        if ($next === ' ' || $next === "\t" || $next === "\r") {
                            $ws .= $next;
                            $position++;
                        } elseif ($next === "\n") {
                            $ws .= $next;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    if ($this->withWhitespace) {
                        $tokens[] = $t = new Token; $t->type = T::WHITESPACE; $t->start = $start; $t->value = $ws;
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
                    $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL; $t->start = $start; $t->value = $char;
                    break;
                case ':':
                    if (($extensions & ClientSideExtension::ALLOW_NAMED_DOUBLE_COLON_PLACEHOLDERS) !== 0) {
                        $name = '';
                        while ($position < $length) {
                            $nextDc = $source[$position];
                            if ($nextDc === '_' || ctype_alpha($nextDc) || (strlen($name) > 0 && ctype_digit($nextDc))) {
                                $name .= $nextDc;
                                $position++;
                            } else {
                                break;
                            }
                        }
                        if ($name !== '') {
                            $tokens[] = $previous = $t = new Token; $t->type = T::PLACEHOLDER | T::DOUBLE_COLON_PLACEHOLDER; $t->start = $start; $t->value = ':' . $name;
                            break;
                        }
                    }
                    $operator = $char;
                    while ($position < $length) {
                        $next2 = $source[$position];
                        if (!isset($this->platform->operators[$operator . $next2])) {
                            if ($operator === ':') {
                                $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL; $t->start = $start; $t->value = $char;
                            } else {
                                $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->start = $start; $t->value = $operator;
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
                    if ($operator === ':') {
                        $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL; $t->start = $start; $t->value = $char;
                    } else {
                        $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->start = $start; $t->value = $operator;
                    }
                    break;
                case '*':
                    // /*!12345 ... */
                    if ($position < $length && $source[$position] === '/') {
                        if ($condition !== null) {
                            // end of optional comment
                            $afterComment = $source[$position + 1];
                            if ($this->withWhitespace && $afterComment !== ' ' && $afterComment !== "\t" && $afterComment !== "\n") {
                                // insert a space in case that optional comment is immediately followed by a non-whitespace token
                                // (resulting token list would serialize into invalid code)
                                $tokens[] = $t = new Token; $t->type = T::WHITESPACE; $t->start = $position + 1; $t->value = ' ';
                            }
                            $condition = null;
                            $position++;
                            break;
                        } elseif ($hint) {
                            // end of optimizer hint
                            $tokens[] = $t = new Token; $t->type = T::SYMBOL; $t->start = $position - 1; $t->value = Symbol::OPTIMIZER_HINT_END;

                            $hint = false;
                            $position++;
                            break;
                        }
                    }
                    // continue
                case '\\':
                    if ($parseOldNullLiteral && $char === '\\' && $position < $length && $source[$position] === 'N') {
                        $position++;
                        $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL; $t->start = $start; $t->value = Symbol::OLD_NULL_SYMBOL;
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
                        $next3 = $source[$position];
                        if (!isset($this->platform->operators[$operator2 . $next3])) {
                            $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->start = $start; $t->value = $operator2;
                            break 2;
                        }
                        if (isset(self::$operatorSymbolsKey[$next3])) {
                            $operator2 .= $next3;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->start = $start; $t->value = $operator2;
                    break;
                case '?':
                    if (($extensions & ClientSideExtension::ALLOW_NUMBERED_QUESTION_MARK_PLACEHOLDERS) !== 0) {
                        $number = '';
                        while ($position < $length) {
                            $nextQm = $source[$position];
                            if (ctype_digit($nextQm)) {
                                $number .= $nextQm;
                                $position++;
                            } else {
                                break;
                            }
                        }
                        if ($number !== '') {
                            $tokens[] = $previous = $t = new Token; $t->type = T::PLACEHOLDER | T::NUMBERED_QUESTION_MARK_PLACEHOLDER; $t->start = $start; $t->value = '?' . $number;
                            break;
                        }
                    }
                    if ($position < $length && ctype_alnum($source[$position])) {
                        $error = Error::lexer("lexer.invalidCharAfterPlaceholder", "Invalid character after placeholder {$source[$position]}.", $position);

                        $tokens[] = $t = new Token; $t->type = T::PLACEHOLDER | T::QUESTION_MARK_PLACEHOLDER | T::INVALID; $t->start = $start; $t->value = '?'; $t->error = $error;
                        $invalid = true;
                        break;
                    }
                    if ($position > 1 && ctype_alnum($source[$position - 2])) {
                        $error = Error::lexer("lexer.invalidCharBeforePlaceholder", "Invalid character before placeholder {$source[$position - 2]}.", $position);

                        $tokens[] = $t = new Token; $t->type = T::PLACEHOLDER | T::QUESTION_MARK_PLACEHOLDER | T::INVALID; $t->start = $start; $t->value = '?'; $t->error = $error;
                        $invalid = true;
                        break;
                    }

                    $tokens[] = $previous = $t = new Token; $t->type = T::PLACEHOLDER | T::QUESTION_MARK_PLACEHOLDER; $t->start = $start; $t->value = $char;
                    break;
                case '@':
                    $var = $char;
                    $second = $source[$position];
                    if ($second === '@') {
                        // @@variable
                        $var .= $second;
                        $position++;
                        if ($source[$position] === '`') {
                            // @@`variable`
                            $position++;
                            $tokens[] = $previous = $this->parseString(T::AT_VARIABLE | T::BACKTICK_QUOTED, $source, $position, '`', '@@');
                            break;
                        }
                        while ($position < $length) {
                            $next4 = $source[$position];
                            if ($next4 === '@' || isset(self::$nameCharsKey[$next4]) || $next4 > "\x7F") {
                                $var .= $next4;
                                $position++;
                            } else {
                                break;
                            }
                        }

                        $yieldDelimiter = false;
                        if (substr($var, -strlen($this->delimiter)) === $this->delimiter) { // str_ends_with()
                            // fucking name-like delimiter after name without whitespace
                            $var = substr($var, 0, -strlen($this->delimiter));
                            $yieldDelimiter = true;
                        }
                        if (strcasecmp(substr($var, 2), 'DEFAULT') === 0) {
                            // todo: probably all magic functions?
                            $error = Error::lexer("lexer.invalidVariableName", "Invalid variable name {$var}.", $position);

                            $tokens[] = $t = new Token; $t->type = T::AT_VARIABLE | T::INVALID; $t->start = $start; $t->value = $var; $t->error = $error;
                            $invalid = true;
                            break;
                        }

                        $tokens[] = $previous = $t = new Token; $t->type = T::AT_VARIABLE; $t->start = $start; $t->value = $var;

                        if ($yieldDelimiter) {
                            $tokens[] = $t = new Token; $t->type = T::DELIMITER; $t->start = $start; $t->value = $this->delimiter;
                            goto yield_token_list;
                        }
                    } elseif ($second === '`') {
                        $position++;
                        $tokens[] = $previous = $this->parseString(T::AT_VARIABLE | T::BACKTICK_QUOTED, $source, $position, $second, '@');
                    } elseif ($second === "'") {
                        $position++;
                        $tokens[] = $previous = $this->parseString(T::AT_VARIABLE | T::SINGLE_QUOTED, $source, $position, $second, '@');
                    } elseif ($second === '"') {
                        $position++;
                        $tokens[] = $previous = $this->parseString(T::AT_VARIABLE | T::DOUBLE_QUOTED, $source, $position, $second, '@');
                    } elseif (isset(self::$userVariableNameCharsKey[$second]) || $second > "\x7F") {
                        // @variable
                        $var .= $second;
                        $position++;
                        while ($position < $length) {
                            $next5 = $source[$position];
                            if (isset(self::$userVariableNameCharsKey[$next5]) || $next5 > "\x7F") {
                                $var .= $next5;
                                $position++;
                            } else {
                                break;
                            }
                        }

                        $yieldDelimiter = false;
                        if (substr($var, -strlen($this->delimiter)) === $this->delimiter) { // str_ends_with()
                            // fucking name-like delimiter after name without whitespace
                            $var = substr($var, 0, -strlen($this->delimiter));
                            $yieldDelimiter = true;
                        }
                        if (strcasecmp(substr($var, 1), 'DEFAULT') === 0) {
                            // todo: probably all magic functions?
                            $error = Error::lexer("lexer.invalidVariableName", "Invalid variable name {$var}.", $position);

                            $tokens[] = $t = new Token; $t->type = T::AT_VARIABLE | T::INVALID; $t->start = $start; $t->value = $var; $t->error = $error;
                            $invalid = true;
                            break;
                        }

                        $tokens[] = $previous = $t = new Token; $t->type = T::AT_VARIABLE; $t->start = $start; $t->value = $var;

                        if ($yieldDelimiter) {
                            $tokens[] = $t = new Token; $t->type = T::DELIMITER; $t->start = $start; $t->value = $this->delimiter;
                            goto yield_token_list;
                        }
                    } else {
                        // simple @ (valid as empty host name)
                        $tokens[] = $previous = $t = new Token; $t->type = T::AT_VARIABLE; $t->start = $start; $t->value = $var;
                        break;
                    }
                    break;
                case '#':
                    // # comment
                    $hashComment = $char;
                    while ($position < $length) {
                        $next6 = $source[$position];
                        $hashComment .= $next6;
                        $position++;
                        if ($next6 === "\n") {
                            break;
                        }
                    }
                    if ($this->withComments) {
                        $tokens[] = $previous = $t = new Token; $t->type = T::LINE_COMMENT | T::HASH_COMMENT; $t->start = $start; $t->value = $hashComment;
                    }
                    break;
                case '/':
                    $next7 = $position < $length ? $source[$position] : '';
                    if ($next7 === '/') {
                        // // comment
                        $position++;
                        $slashComment = $char . $next7;
                        while ($position < $length) {
                            $next7 = $source[$position];
                            $slashComment .= $next7;
                            $position++;
                            if ($next7 === "\n") {
                                break;
                            }
                        }
                        if ($this->withComments) {
                            $tokens[] = $previous = $t = new Token; $t->type = T::LINE_COMMENT | T::DOUBLE_SLASH_COMMENT; $t->start = $start; $t->value = $slashComment;
                        }
                    } elseif ($next7 === '*') {
                        $position++;

                        $optional = $source[$position] === '!';
                        $beforeComment = $source[$position - 3];
                        // todo: Maria
                        $validOptional = true;
                        if ($optional) {
                            if (strlen($source) > $position + 1 && $source[$position + 1] === '*' && $source[$position + 2] === '/') {
                                // /*!*/
                                $position += 3;
                                break;
                            }
                            $validOptional = preg_match('~^([Mm]?!(?:00000|[1-9]\d{4,5})?)\D~', substr($source, $position, 10), $m) === 1;
                            if ($validOptional) {
                                $versionId = strtoupper(str_replace('!', '', $m[1]));
                                if ($this->platform->interpretOptionalComment($versionId)) {
                                    if ($this->withWhitespace && $beforeComment !== ' ' && $beforeComment !== "\t" && $beforeComment !== "\n") {
                                        // insert a space in case that optional comment was immediately following a non-whitespace token
                                        // (resulting token list would serialize into invalid code)
                                        $tokens[] = $t = new Token; $t->type = T::WHITESPACE; $t->start = $position - 3; $t->value = ' ';
                                    }
                                    $condition = $versionId;
                                    $position += strlen($versionId) + 1;

                                    // continue parsing as conditional code
                                    break;
                                }
                            }
                        }

                        $isHint = $source[$position] === '+';
                        if ($isHint && $parseOptimizerHints) {
                            $optimizerHintCanFollow = ($previous->type & TokenType::RESERVED) !== 0
                                && in_array(strtoupper($previous->value), [Keyword::SELECT, Keyword::INSERT, Keyword::REPLACE, Keyword::UPDATE, Keyword::DELETE], true);

                            if ($optimizerHintCanFollow) {
                                $hint = true;
                                $position++;
                                $tokens[] = $t = new Token; $t->type = T::SYMBOL; $t->start = $start; $t->value = Symbol::OPTIMIZER_HINT_START;
                                break;
                            }
                        }

                        // parse as a regular comment
                        $commentDepth++;
                        $comment = $char . $next7;
                        $terminated = false;
                        while ($position < $length) {
                            $next8 = $source[$position];
                            if ($next8 === '/' && ($position + 1 < $length) && $source[$position + 1] === '*') {
                                $comment .= $next8 . $source[$position + 1];
                                $position += 2;
                                $commentDepth++;
                            } elseif ($next8 === '*' && ($position + 1 < $length) && $source[$position + 1] === '/') {
                                $comment .= $next8 . $source[$position + 1];
                                $position += 2;
                                $commentDepth--;
                                if ($commentDepth === 0) {
                                    $terminated = true;
                                    break;
                                }
                            } elseif ($next8 === "\n") {
                                $comment .= $next8;
                                $position++;
                            } else {
                                $comment .= $next8;
                                $position++;
                            }
                        }
                        if (!$terminated) {
                            $error = Error::lexer("lexer.unterminatedComment", "End of comment not found.", $position);

                            $tokens[] = $t = new Token; $t->type = T::BLOCK_COMMENT | T::INVALID; $t->start = $start; $t->value = $comment; $t->error = $error;
                            $invalid = true;
                            break;
                        } elseif (!$validOptional) {
                            $condition = null;
                            $error = Error::lexer("lexer.invalidOptionalComment", "Invalid optional comment: {$comment}", $position);

                            $tokens[] = $t = new Token; $t->type = T::BLOCK_COMMENT | T::OPTIONAL_COMMENT | T::INVALID; $t->start = $start; $t->value = $comment; $t->error = $error;
                            $invalid = true;
                            break;
                        }

                        if ($this->withComments) {
                            if ($optional) {
                                // /*!12345 comment (when not interpreted as code) */
                                $tokens[] = $t = new Token; $t->type = T::BLOCK_COMMENT | T::OPTIONAL_COMMENT; $t->start = $start; $t->value = $comment;
                            } elseif ($hint) {
                                // /*+ comment */ (when not interpreted as code)
                                $tokens[] = $t = new Token; $t->type = T::BLOCK_COMMENT | T::OPTIMIZER_HINT_COMMENT; $t->start = $start; $t->value = $comment;
                            } else {
                                // /* comment */
                                $tokens[] = $t = new Token; $t->type = T::BLOCK_COMMENT; $t->start = $start; $t->value = $comment;
                            }
                        }
                    } else {
                        $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->start = $start; $t->value = $char;
                    }
                    break;
                case '"':
                    $type = ($this->sqlMode->fullValue & SqlMode::ANSI_QUOTES) !== 0
                        ? T::QUOTED_NAME | T::DOUBLE_QUOTED
                        : T::STRING | T::DOUBLE_QUOTED;

                    $tokens[] = $previous = $this->parseString($type, $source, $position, '"');
                    break;
                case "'":
                    $tokens[] = $previous = $this->parseString(T::STRING | T::SINGLE_QUOTED, $source, $position, "'");
                    break;
                case '`':
                    $tokens[] = $previous = $this->parseString(T::QUOTED_NAME | T::BACKTICK_QUOTED, $source, $position, '`');
                    break;
                case '.':
                    $afterDot = $position < $length ? $source[$position] : '';
                    // .123 cannot follow a name, e.g.: "select 1ea10.1a20, ...", but can follow a keyword, e.g.: "INTERVAL .4 SECOND"
                    if (isset(self::$numbersKey[$afterDot]) && (($previous->type & T::NAME) === 0 || ($previous->type & T::KEYWORD) !== 0)) {
                        if (preg_match(self::ANCHORED_NUMBER_REGEXP, $source, $m, PREG_UNMATCHED_AS_NULL, $position - 1) !== 0) {
                            $token = $this->numberToken($source, $position, $m); // @phpstan-ignore argument.type
                            if ($token !== null) {
                                $tokens[] = $previous = $token;
                                break;
                            }
                        }
                    }
                    $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL; $t->start = $start; $t->value = $char;
                    break;
                case '-':
                    $second = $position < $length ? $source[$position] : '';

                    if ($second === '-') {
                        $third = $position + 1 < $length ? $source[$position + 1] : '';

                        if ($third === "\n") {
                            // --\n
                            $position += 2;
                            if ($this->withComments) {
                                $tokens[] = $previous = $t = new Token; $t->type = T::DOUBLE_HYPHEN_COMMENT; $t->start = $start; $t->value = "--\n";
                            }
                            break;
                        }
                        if ($third === "\r") {
                            $fourth = $position + 2 < $length ? $source[$position + 2] : '';
                            if ($fourth === "\n") {
                                // --\r\n
                                $position += 3;
                                if ($this->withComments) {
                                    $tokens[] = $previous = $t = new Token; $t->type = T::DOUBLE_HYPHEN_COMMENT; $t->start = $start; $t->value = "--\r\n";
                                }
                                break;
                            }
                        }
                        if ($third === ' ') {
                            // -- comment
                            $endOfLine = strpos($source, "\n", $position);
                            if ($endOfLine === false) {
                                $endOfLine = strlen($source);
                            }
                            $line = substr($source, $position - 1, $endOfLine - $position + 2);
                            $position += strlen($line) - 1;

                            if ($this->withComments) {
                                $tokens[] = $previous = $t = new Token; $t->type = T::DOUBLE_HYPHEN_COMMENT; $t->start = $start; $t->value = $line;
                            }
                            break;
                        }

                        $tokens[] = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->start = $start; $t->value = '-';
                        break;
                    }

                    $numberCanFollow = ($previous->type & T::END) !== 0
                        || (($previous->type & T::SYMBOL) !== 0 && $previous->value !== ')' && $previous->value !== '?')
                        || (($previous->type & T::KEYWORD) !== 0 && strcasecmp($previous->value, Keyword::DEFAULT) === 0);
                    if ($numberCanFollow) {
                        if (preg_match(self::ANCHORED_NUMBER_REGEXP, $source, $m, PREG_UNMATCHED_AS_NULL, $position - 1) !== 0) {
                            $token = $this->numberToken($source, $position, $m); // @phpstan-ignore argument.type
                            if ($token !== null) {
                                $tokens[] = $previous = $token;
                                break;
                            }
                        }
                    }

                    $operator3 = $char;
                    while ($position < $length) {
                        $next10 = $source[$position];
                        if (!isset($this->platform->operators[$operator3 . $next10])) {
                            $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->start = $start; $t->value = $operator3;
                            break 2;
                        }
                        if (isset(self::$operatorSymbolsKey[$next10])) {
                            $operator3 .= $next10;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->start = $start; $t->value = $operator3;
                    break;
                case '+':
                    $afterPlus = $position < $length ? $source[$position] : '';
                    $numberCanFollow = ($previous->type & T::END) !== 0
                        || (($previous->type & T::SYMBOL) !== 0 && $previous->value !== ')' && $previous->value !== '?')
                        || (($previous->type & T::KEYWORD) !== 0 && $previous->value === Keyword::DEFAULT);

                    if ($numberCanFollow && ($afterPlus === '.' || isset(self::$numbersKey[$afterPlus]))) {
                        if (preg_match(self::ANCHORED_NUMBER_REGEXP, $source, $m, PREG_UNMATCHED_AS_NULL, $position - 1) !== 0) {
                            $token = $this->numberToken($source, $position, $m); // @phpstan-ignore argument.type
                            if ($token !== null) {
                                $tokens[] = $previous = $token;
                                break;
                            }
                        }
                    }

                    $operator4 = $char;
                    while ($position < $length) {
                        $next12 = $source[$position];
                        if (!isset($this->platform->operators[$operator4 . $next12])) {
                            $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->start = $start; $t->value = $operator4;
                            break 2;
                        }
                        if (isset(self::$operatorSymbolsKey[$next12])) {
                            $operator4 .= $next12;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    $tokens[] = $previous = $t = new Token; $t->type = T::SYMBOL | T::OPERATOR; $t->start = $start; $t->value = $operator4;
                    break;
                case '0':
                    $next13 = $position < $length ? $source[$position] : '';
                    if ($next13 === 'b') {
                        // 0b00100011
                        $position++;
                        $bits = '';
                        while ($position < $length) {
                            $next13 = $source[$position];
                            if ($next13 === '0' || $next13 === '1') {
                                $bits .= $next13;
                                $position++;
                            } elseif (isset(self::$nameCharsKey[$next13]) || $next13 > "\x7F") {
                                // name pretending to be a binary literal :E
                                $position -= strlen($bits) + 1;
                                break;
                            } else {
                                $tokens[] = $previous = $t = new Token; $t->type = T::BINARY_LITERAL; $t->start = $start; $t->value = $bits;
                                break 2;
                            }
                        }
                    } elseif ($next13 === 'x') {
                        // 0x001f
                        $position++;
                        $bits = '';
                        while ($position < $length) {
                            $next13 = $source[$position];
                            if (isset(self::$hexadecKey[$next13])) {
                                $bits .= $next13;
                                $position++;
                            } elseif (isset(self::$nameCharsKey[$next13]) || $next13 > "\x7F") {
                                // name pretending to be a hexadecimal literal :E
                                $position -= strlen($bits) + 1;
                                break;
                            } else {
                                $tokens[] = $previous = $t = new Token; $t->type = T::HEXADECIMAL_LITERAL; $t->start = $start; $t->value = strtolower($bits);
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
                    // UUID
                    if ($length >= $position + 35 && preg_match(self::ANCHORED_UUID_REGEXP, $source, $m, 0, $position - 1) !== 0) {
                        $uuid = $m[0]; // @phpstan-ignore offsetAccess.notFound
                        $position += 35;
                        $tokens[] = $previous = $t = new Token; $t->type = T::UUID; $t->start = $start; $t->value = $uuid;
                        break;
                    }
                    // IPv4 todo: is this real or is it always quoted?
                    if ($length >= $position + 6 && preg_match(self::ANCHORED_IP_V4_REGEXP, $source, $m, 0, $position - 1) !== 0) {
                        $ipv4 = $m[0]; // @phpstan-ignore offsetAccess.notFound
                        $position += strlen($ipv4) - 1;
                        $tokens[] = $previous = $t = new Token; $t->type = T::STRING; $t->start = $start; $t->value = $ipv4;
                        break;
                    }
                    // number
                    if (preg_match(self::ANCHORED_NUMBER_REGEXP, $source, $m, PREG_UNMATCHED_AS_NULL, $position - 1) !== 0) {
                        $token = $this->numberToken($source, $position, $m); // @phpstan-ignore argument.type
                        if ($token !== null) {
                            $tokens[] = $previous = $token;
                            break;
                        }
                    }
                    // continue
                case 'B':
                case 'b':
                    // b'01'
                    // B'01'
                    if (($char === 'B' || $char === 'b') && $position < $length && $source[$position] === '\'') {
                        $position++;
                        $bits = $next14 = '';
                        while ($position < $length) {
                            $next14 = $source[$position];
                            if ($next14 === '\'') {
                                $position++;
                                break;
                            } else {
                                $bits .= $next14;
                                $position++;
                            }
                        }
                        if (ltrim($bits, '01') === '') {
                            $tokens[] = $previous = $t = new Token; $t->type = T::BINARY_LITERAL; $t->start = $start; $t->value = $bits;
                        } else {
                            $error = Error::lexer("lexer.invalidBinaryLiteral", "Invalid binary literal", $position);
                            $value = $char . '\'' . $bits . $next14;

                            $tokens[] = $previous = $t = new Token; $t->type = T::BINARY_LITERAL | T::INVALID; $t->start = $start; $t->value = $value; $t->error = $error;
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
                    // UUID
                    if ($length >= $position + 35 && preg_match(self::ANCHORED_UUID_REGEXP, $source, $m, 0, $position - 1) !== 0) {
                        $uuid2 = $m[0]; // @phpstan-ignore offsetAccess.notFound
                        $position += 35;
                        $tokens[] = $previous = $t = new Token; $t->type = T::UUID; $t->start = $start; $t->value = $uuid2;
                        break;
                    }
                    // continue
                case 'X':
                case 'x':
                    if (($char === 'X' || $char === 'x') && $position < $length && $source[$position] === '\'') {
                        $position++;
                        $bits = $next15 = '';
                        while ($position < $length) {
                            $next15 = $source[$position];
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
                            $tokens[] = $previous = $t = new Token; $t->type = T::BIT_STRING | T::HEXADECIMAL; $t->start = $start; $t->value = $bits;
                        } else {
                            $error = Error::lexer("lexer.invalidHexadecimalLiteral", "Invalid hexadecimal literal", $position);
                            $value = $char . '\'' . $bits . $next15;

                            $tokens[] = $previous = $t = new Token; $t->type = T::BIT_STRING | T::HEXADECIMAL | T::INVALID; $t->start = $start; $t->value = $value; $t->error = $error;
                            $invalid = true;
                            break;
                        }
                        break;
                    }
                    // continue
                case 'N':
                    $afterN = $position < $length ? $source[$position] : null;
                    if ($char === 'N' && $afterN === '"') {
                        $position++;
                        $type = ($this->sqlMode->fullValue & SqlMode::ANSI_QUOTES) !== 0
                            ? T::QUOTED_NAME | T::DOUBLE_QUOTED
                            : T::STRING | T::DOUBLE_QUOTED;

                        $tokens[] = $previous = $this->parseString($type, $source, $position, '"', 'N');
                        break;
                    } elseif ($char === 'N' && $afterN === "'") {
                        $position++;
                        $tokens[] = $previous = $this->parseString(T::STRING | T::SINGLE_QUOTED, $source, $position, "'", 'N');
                        break;
                    } elseif ($char === 'N' && $afterN === '`') {
                        $position++;
                        $tokens[] = $previous = $this->parseString(T::QUOTED_NAME | T::BACKTICK_QUOTED, $source, $position, "`", 'N');
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
                        $next17 = $source[$position];
                        if (isset(self::$nameCharsKey[$next17]) || $next17 > "\x7F") {
                            $name .= $next17;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    $yieldDelimiter = false;
                    if (substr($name, -strlen($this->delimiter)) === $this->delimiter) { // str_ends_with()
                        // fucking name-like delimiter after name without whitespace
                        $name = substr($name, 0, -strlen($this->delimiter));
                        $yieldDelimiter = true;
                    }

                    $upper = strtoupper($name);
                    if (isset($this->platform->reserved[$upper])) {
                        if (isset($this->platform->operators[$upper])) {
                            $tokens[] = $previous = $t = new Token; $t->type = T::UNQUOTED_NAME | T::KEYWORD | T::RESERVED | T::OPERATOR; $t->start = $start; $t->value = $name;
                        } else {
                            $tokens[] = $previous = $t = new Token; $t->type = T::UNQUOTED_NAME | T::KEYWORD | T::RESERVED; $t->start = $start; $t->value = $name;
                        }
                    } elseif (isset($this->platform->nonReserved[$upper])) {
                        $tokens[] = $previous = $t = new Token; $t->type = T::UNQUOTED_NAME | T::KEYWORD; $t->start = $start; $t->value = $name;
                    } elseif ($upper === Keyword::DELIMITER && $allowDelimiterDefinition) {
                        $tokens[] = $t = new Token; $t->type = T::UNQUOTED_NAME | T::KEYWORD; $t->start = $start; $t->value = $name;
                        $start = $position;
                        $whitespace = $this->parseWhitespace($source, $position);
                        if ($this->withWhitespace) {
                            $tokens[] = $t = new Token; $t->type = T::WHITESPACE; $t->start = $start; $t->value = $whitespace;
                        }
                        $start = $position;
                        $del = '';
                        while ($position < $length) {
                            $next18 = $source[$position];
                            if ($next18 === "\n" || $next18 === "\r" || $next18 === "\t" || $next18 === ' ') {
                                break;
                            } else {
                                $del .= $next18;
                                $position++;
                            }
                        }
                        if ($del === '') {
                            $error = Error::lexer("lexer.invalidDelimiter", "Delimiter not found", $position);

                            $tokens[] = $previous = $t = new Token; $t->type = T::INVALID; $t->start = $start; $t->value = $del; $t->error = $error;
                            $invalid = true;
                            break;
                        }
                        if (isset($this->platform->reserved[strtoupper($del)])) {
                            $error = Error::lexer("lexer.invalidDelimiter", "Delimiter can not be a reserved word", $position);

                            $tokens[] = $previous = $t = new Token; $t->type = T::DELIMITER_DEFINITION | T::INVALID; $t->start = $start; $t->value = $del; $t->error = $error;
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
                        $this->session->setDelimiter($del);
                        $tokens[] = $previous = $t = new Token; $t->type = T::DELIMITER_DEFINITION; $t->start = $start; $t->value = $this->delimiter;
                    } else {
                        $tokens[] = $previous = $t = new Token; $t->type = T::UNQUOTED_NAME; $t->start = $start; $t->value = $name;
                    }
                    if ($yieldDelimiter) {
                        $tokens[] = $t = new Token; $t->type = T::DELIMITER; $t->start = $start; $t->value = $this->delimiter;
                        goto yield_token_list;
                    } elseif (($previous->type & T::DELIMITER_DEFINITION) !== 0) {
                        goto yield_token_list;
                    }
                    break;
                default:
                    $name2 = $char;
                    while ($position < $length) {
                        $next19 = $source[$position];
                        if (isset(self::$nameCharsKey[$next19]) || $next19 > "\x7F") {
                            $name2 .= $next19;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    $tokens[] = $previous = $t = new Token; $t->type = T::UNQUOTED_NAME; $t->start = $start; $t->value = $name2;
            }
        }

        yield_token_list:
        if ($tokens !== []) {
            if ($condition !== null) {
                $lastToken = end($tokens);
                $condition = null;
                $error = Error::lexer("lexer.unterminatedOptionalComment", "End of optional comment not found.", $lastToken->start);
                $tokens[] = $t = new Token; $t->type = T::END + T::INVALID; $t->start = 0; $t->value = ''; $t->error = $error;
                $invalid = true;
            }
            if ($hint) {
                $lastToken = end($tokens);
                $hint = false;
                $error = Error::lexer("lexer.unterminatedOptimizerHint", "End of optimizer hint not found.", $lastToken->start);
                $tokens[] = $t = new Token; $t->type = T::END + T::INVALID; $t->start = 0; $t->value = ''; $t->error = $error;
                $invalid = true;
            }

            yield new TokenList($source, $tokens, $this->platform, $this->session, $autoSkip, $invalid);

            $tokens = [];
            $invalid = false;
        }

        if ($position < $length) {
            goto continue_tokenizing;
        }
    }

    private function parseWhitespace(string $source, int &$position): string
    {
        $length = strlen($source);
        $whitespace = '';
        while ($position < $length) {
            $next = $source[$position];
            if ($next === ' ' || $next === "\t" || $next === "\r" || $next === "\n") {
                $whitespace .= $next;
                $position++;
            } else {
                break;
            }
        }

        return $whitespace;
    }

    private function parseString(int $tokenType, string $source, int &$position, string $quote, string $prefix = ''): Token
    {
        $startAt = $position - 1 - strlen($prefix);
        $length = strlen($source);

        $ansi = ($this->sqlMode->fullValue & SqlMode::ANSI_QUOTES) !== 0;
        $isAtVariable = ($tokenType & T::AT_VARIABLE) !== 0;
        $mayHaveBackslashes = ($tokenType & (T::STRING | T::SINGLE_QUOTED)) !== 0 || (!$ansi && ($tokenType & T::DOUBLE_QUOTED) !== 0);
        $backslashes = $mayHaveBackslashes && ($this->sqlMode->fullValue & SqlMode::NO_BACKSLASH_ESCAPES) === 0;

        $orig = [$quote];
        $escaped = false;
        $finished = false;
        while ($position < $length) {
            $next = $source[$position];
            // todo: check for \0 in names?
            if ($next === $quote) {
                $orig[] = $next;
                $position++;
                if ($escaped) {
                    $escaped = false;
                } elseif ($position < $length && $source[$position] === $quote) {
                    $escaped = true;
                } else {
                    $finished = true;
                    break;
                }
            } elseif ($next === "\n") {
                $orig[] = $next;
                $position++;
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
            $error = Error::lexer("lexer.unterminatedStringLiteral", "End of string not found. Starts with " . substr($source, $startAt - 1, 100), $position);

            $t = new Token; $t->type = $tokenType | T::INVALID; $t->start = $startAt; $t->value = $prefix . $orig; $t->error = $error;

            return $t;
        }

        // remove quotes
        $value = substr($orig, 1, -1);
        // unescape double quotes
        $value = str_replace($quote . $quote, $quote, $value);
        if ($backslashes) {
            // unescape backslashes only in string context
            $value = str_replace(self::$escapeKeys, self::$escapeValues, $value);
        }

        $t = new Token; $t->type = $tokenType; $t->start = $startAt; $t->value = ($isAtVariable ? $prefix : '') . $value;

        return $t;
    }

    /**
     * @param array{string, ?string, string, ?string, ?string, ?string} $m
     */
    private function numberToken(string $source, int &$position, array $m): ?Token
    {
        [$value, $sign, $base, $e, $expSign, $exponent] = $m;

        $startAt = $position - 1;
        $len = strlen($value) - 1;

        $intBase = ctype_digit($base);
        if ($intBase) {
            if ($e !== null && $exponent === '') {
                // dumb name followed by +/- operator
                return null;
            }
            $nextChar = $source[$position + $len] ?? '';
            if ($e === null && (isset(self::$nameCharsKey[$nextChar]) || $nextChar > "\x7F")) {
                // followed by a name character while not having '.' or exponent - this is a prefix of a name, not a number
                return null;
            }
        }

        $type = T::NUMBER;
        $position += $len;

        if ($e !== null && $exponent === '') {
            $error = Error::lexer("lexer.invalidNumericLiteral", "Invalid number exponent in '{$value}'", $position);

            $t = new Token; $t->type = $type | T::INVALID; $t->start = $startAt; $t->value = $value; $t->error = $error;

            return $t;
        }

        // todo: is "+42" considered uint?
        if ($intBase && $e === null) {
            $type |= T::INT;

            if ($sign === '') {
                $t = new Token; $t->type = $type | T::UINT; $t->start = $startAt; $t->value = $value;

                return $t;
            }
        }

        while (strlen($sign) > 1 && $sign[0] === '-' && $sign[1] === '-') { // @phpstan-ignore argument.type, offsetAccess.notFound, offsetAccess.notFound
            $sign = substr($sign, 2); // @phpstan-ignore argument.type
        }

        $v = $sign . $base . ($e !== null ? 'e' : '') . $expSign . $exponent;
        $t = new Token; $t->type = $type; $t->start = $startAt; $t->value = $v;

        return $t;
    }

}
