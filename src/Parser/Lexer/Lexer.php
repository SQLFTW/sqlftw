<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Lexer;

use SqlFtw\Platform\Settings;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Keyword;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenType;

/**
 * todo:
 * - unescaping strings
 * - "0.", ".0"
 * - Date and Time Literals
 * - Hexadecimal Literals
 * - Bit-Value Literals
 * - User-Defined Variables (@var)
 * - Mysql string charset declaration (_utf* & N)
 * - Optional comments /*! ... * /
 * - Hint comments /*+ ... * /
 * - PostgreSql dollar strings
 */
class Lexer
{
    use \Dogma\StrictBehaviorMixin;

    private const EOL = "\n";

    private const WHITESPACE = [' ', "\t", "\r", "\n"];

    private const NUMBERS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const HEXADECIMALS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'];

    private const LETTERS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];

    private const OPERATOR_SYMBOLS = ['!', '$', '%', '&', '*', '+', '-', '/', ':', '<', '=', '>', '?', '@', '\\', '^', '|', '~'];

    private const OPERATOR_KEYWORDS = [
        Keyword::AND,
        Keyword::OR,
        Keyword::XOR,
        Keyword::NOT,
        Keyword::IN,
        Keyword::IS,
        Keyword::LIKE,
        Keyword::RLIKE,
        Keyword::REGEXP,
        Keyword::SOUNDS,
        Keyword::BETWEEN,
        Keyword::DIV,
        Keyword::MOD,
        Keyword::INTERVAL,
        Keyword::BINARY,
        Keyword::COLLATE,
        Keyword::CASE,
        Keyword::WHEN,
        Keyword::THAN,
        Keyword::ELSE,
    ];

    public const UUID_REGEXP = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}/i';

    /** @var bool */
    private $withComments;

    /** @var bool */
    private $withWhitespace;

    /** @var string */
    private $delimiter;

    /** @var \SqlFtw\Sql\Charset */
    private $charset;

    /** @var bool */
    private $ansiQuotes;

    /**
     * @param bool $withComments
     * @param bool $withWhitespace
     */
    public function __construct(bool $withComments = true, bool $withWhitespace = false)
    {
        $this->withComments = $withComments;
        $this->withWhitespace = $withWhitespace;
    }

    /**
     * Tokenize SQL code. Expects line endings to be converted to "\n" and UTF-8 encoding.
     * @param string $string
     * @param string $delimiter
     * @return \SqlFtw\Parser\Token[]
     */
    public function tokenize(string $string, Settings $settings): array
    {
        $this->delimiter = $settings->getDelimiter() ?? ';';
        $this->charset = $settings->getCharset() ?? Charset::get(Charset::UTF_8);
        $this->ansiQuotes = $settings->ansiQuotes();

        $string = new StringBuffer($string);

        $tokens = [];
        while ($char = $string->get()) {
            $position = $string->position;
            //dump($char);
            //dump($position);

            /*
            $string->position++;
            $string->column++;
            switch ($char) {
                case ' ':
                case "\t":
                    ///
                    break;
                case "\r":
                case "\n":
                    $value = $string->consumeAny(self::WHITESPACE);
                    if ($this->withWhitespace) {
                        yield new Token(TokenType::WHITESPACE, $position, $value);
                    }
                    break;
                case '(':
                    yield new Token(TokenType::SYMBOL | TokenType::LEFT_PARENTHESIS, $position, $char);
                    break;
                case ')':
                    yield new Token(TokenType::SYMBOL | TokenType::RIGHT_PARENTHESIS, $position, $char);
                    break;
                case '[':
                    yield new Token(TokenType::SYMBOL | TokenType::LEFT_SQUARE_BRACKET, $position, $char);
                    break;
                case ']':
                    yield new Token(TokenType::SYMBOL | TokenType::RIGHT_SQUARE_BRACKET, $position, $char);
                    break;
                case '{':
                    yield new Token(TokenType::SYMBOL | TokenType::LEFT_CURLY_BRACKET, $position, $char);
                    break;
                case '}':
                    yield new Token(TokenType::SYMBOL | TokenType::RIGHT_CURLY_BRACKET, $position, $char);
                    break;
                case ',':
                    yield new Token(TokenType::SYMBOL | TokenType::DOT, $position, $char);
                    break;
                case ';';
                    yield new Token(TokenType::SYMBOL | TokenType::SEMICOLON, $position, $char);
                    break;
                case ':':
                    /// operator
                    /// :
                case '?':
                    /// operator
                    /// ?
                    break;
                case '@':
                    /// @name
                    /// operator
                    break;
                case '#':
                    /// comment
                    break;
                case '/':
                    /// // comment
                    /// /*! comment
                    /// /*+ comment
                    /// /* comment
                    /// operator
                    break;
                case '"':
                case '\'':
                case '`':
                    /// name
                    /// string
                    break;
                case '.':
                    /// number
                    $tokens[] = new Token(TokenType::SYMBOL | TokenType::DOT, $position, $char);
                    break;
                case '-':
                    /// -- comment
                case '+':
                    /// number
                    $tokens[] = new Token(TokenType::SYMBOL | TokenType::OPERATOR, $position, $string->consumeAny(self::OPERATOR_SYMBOLS));
                    break;
                case '0':
                case '1':
                case '2':
                case '3':
                case '4':
                case '5':
                case '6':
                case '7':
                case '8':
                case '9':
                    /// UUID
                    /// number
                    break;
                case 'A':
                case 'a':
                case 'B':
                case 'b':
                case 'C':
                case 'c':
                case 'D':
                case 'd':
                case 'E':
                case 'e':
                case 'F':
                case 'f':
                    /// UUID
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
                case 'X':
                case 'x':
                case 'Y':
                case 'y':
                case 'Z':
                case 'z':
                    /// keyword
                    /// name
                    break;
                case '_':
                    /// encoding declaration
                    /// name
                    break;
                default:
                    /// utf characters
                    /// error
            }
            */

            if (in_array($char, self::WHITESPACE)) {
                $value = $string->consumeAny(self::WHITESPACE);
                if ($this->withWhitespace) {
                    $tokens[] = new Token(TokenType::WHITESPACE, $position, $value);
                }
            } elseif ($string->follows($this->delimiter)) {
                $string->consume($this->delimiter);
                $tokens[] = new Token(TokenType::SYMBOL | TokenType::DELIMITER, $position, $this->delimiter);
            } elseif ($char === '.') {
                $string->consume($char);
                $tokens[] = new Token(TokenType::SYMBOL | TokenType::DOT, $position, $char);
            } elseif ($char === ',') {
                $string->consume($char);
                $tokens[] = new Token(TokenType::SYMBOL | TokenType::COMMA, $position, $char);
            } elseif ($char === ';') {
                $string->consume($char);
                $tokens[] = new Token(TokenType::SYMBOL | TokenType::SEMICOLON, $position, $char);
            } elseif ($char === '?' && !$string->followsAny(self::OPERATOR_SYMBOLS, 1)) {
                $string->consume($char);
                $tokens[] = new Token(TokenType::SYMBOL | TokenType::PLACEHOLDER, $position, $char);
            } elseif ($char === '@' && $string->followsAny(self::LETTERS + ['_'], 1)) {
                $string->consume($char);
                ///$string->consumeTillNext()
            } elseif (in_array($char, self::HEXADECIMALS) && $value = $string->tryMatch(self::UUID_REGEXP)) {
                $string->consume($value);
                $tokens[] = new Token(TokenType::VALUE | TokenType::UUID, $position, $value);
            } elseif (in_array($char, self::NUMBERS)) {
                $value = $this->parseNumber($string, '');
                $tokens[] = new Token(TokenType::VALUE | TokenType::NUMBER, $position, $value);
            } elseif (($char === '-' || $char === '+') && $string->followsAny(self::NUMBERS, 1)) {
                $string->consume($char);
                $value = $this->parseNumber($string, $char);
                $tokens[] = new Token(TokenType::VALUE | TokenType::NUMBER, $position, $value);
            } elseif ($char === '(') {
                $string->consume($char);
                $tokens[] = new Token(TokenType::SYMBOL | TokenType::LEFT_PARENTHESIS, $position, $char);
            } elseif ($char === ')') {
                $string->consume($char);
                $tokens[] = new Token(TokenType::SYMBOL | TokenType::RIGHT_PARENTHESIS, $position, $char);
            } elseif ($char === '[') {
                $string->consume($char);
                $tokens[] = new Token(TokenType::SYMBOL | TokenType::LEFT_SQUARE_BRACKET, $position, $char);
            } elseif ($char === ']') {
                $string->consume($char);
                $tokens[] = new Token(TokenType::SYMBOL | TokenType::RIGHT_SQUARE_BRACKET, $position, $char);
            } elseif ($char === '{') {
                $string->consume($char);
                $tokens[] = new Token(TokenType::SYMBOL | TokenType::LEFT_CURLY_BRACKET, $position, $char);
            } elseif ($char === '}') {
                $string->consume($char);
                $tokens[] = new Token(TokenType::SYMBOL | TokenType::RIGHT_CURLY_BRACKET, $position, $char);
            } elseif ($char === '#') {
                $value = $string->consumeTillEofOrNext(self::EOL);
                if ($this->withComments) {
                    $tokens[] = new Token(TokenType::COMMENT | TokenType::HASH_COMMENT, $position, $value);
                }
            } elseif ($string->follows('--')) {
                $value = $string->consumeTillEofOrNext(self::EOL);
                if ($this->withComments) {
                    $tokens[] = new Token(TokenType::COMMENT | TokenType::DOUBLE_HYPHEN_COMMENT, $position, $value);
                }
            } elseif ($string->follows('//')) {
                $value = $string->consumeTillEofOrNext(self::EOL);
                if ($this->withComments) {
                    $tokens[] = new Token(TokenType::COMMENT | TokenType::DOUBLE_SLASH_COMMENT, $position, $value);
                }
            } elseif ($string->follows('/*')) {
                $value = $string->consumeTillNext('*/', true);
                if ($this->withComments) {
                    $tokens[] = new Token(TokenType::COMMENT | TokenType::BLOCK_COMMENT, $position, $value);
                }
            } elseif (in_array($char, self::OPERATOR_SYMBOLS)) {
                $tokens[] = new Token(TokenType::SYMBOL | TokenType::OPERATOR, $position, $string->consumeAny(self::OPERATOR_SYMBOLS));
            } elseif ($char === '\'' || $char === '"' || $char === '`') {
                $string->skip(1);
                $value = $string->consumeTillNextNonEscaped($char, '\\');
                if ($char === '\'') {
                    $tokens[] = new Token(TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, $position, $value);
                } elseif ($char === '"') {
                    $type = $this->ansiQuotes
                        ? TokenType::NAME | TokenType::DOUBLE_QUOTED_STRING
                        : TokenType::VALUE | TokenType::STRING | TokenType::DOUBLE_QUOTED_STRING;
                    $tokens[] = new Token($type, $position, $value);
                } else {
                    $tokens[] = new Token(TokenType::NAME | TokenType::BACKTICK_QUOTED_STRING, $position, $value);
                }
                $string->skip(1);
            } elseif (preg_match('/[a-z_]/i', $char)) {
                $word = $string->consumeMatching('/[a-z0-9_]/i');
                $upper = strtoupper($word);
                if (Keyword::isValid($upper)) {
                    if ($upper === Keyword::DELIMITER) {
                        $tokens[] = new Token(TokenType::KEYWORD, $position, $upper);
                        $position = $string->position;
                        $whitespace = $string->consumeAny(self::WHITESPACE);
                        if ($this->withWhitespace) {
                            $tokens[] = new Token(TokenType::WHITESPACE, $position, $whitespace);
                        }
                        $position = $string->position;
                        $delimiter = $string->consumeAny(array_merge(self::OPERATOR_SYMBOLS, [';']));
                        $this->delimiter = $delimiter;
                        $tokens[] = new Token(TokenType::SYMBOL | TokenType::DELIMITER_DEFINITION, $position, $delimiter);
                    } elseif ($upper === Keyword::NULL) {
                        $tokens[] = new Token(TokenType::KEYWORD | TokenType::VALUE | TokenType::NULL, $position, null);
                    } elseif ($upper === Keyword::TRUE) {
                        $tokens[] = new Token(TokenType::KEYWORD | TokenType::VALUE | TokenType::BOOLEAN, $position, true);
                    } elseif ($upper === Keyword::FALSE) {
                        $tokens[] = new Token(TokenType::KEYWORD | TokenType::VALUE | TokenType::BOOLEAN, $position, false);
                    } elseif (in_array($upper, self::OPERATOR_KEYWORDS)) {
                        $tokens[] = new Token(TokenType::KEYWORD | TokenType::OPERATOR, $position, $upper);
                    } else {
                        $tokens[] = new Token(TokenType::KEYWORD, $position, $upper);
                    }
                } else {
                    $tokens[] = new Token(TokenType::NAME | TokenType::UNQUOTED_NAME, $position, $word);
                }
            } else {
                throw new \SqlFtw\Parser\Lexer\UnrecognizedTokenException($char, $position, $string->getContext());
            }
        };

        return $tokens;
    }

    /**
     * @param \SqlFtw\Parser\Lexer\StringBuffer $string
     * @param string $sign
     * @return int|float|string
     */
    private function parseNumber(StringBuffer $string, string $sign)
    {
        $value = $sign . $string->consumeAny(self::NUMBERS);
        $dot = $string->mayConsume('.');
        if ($dot !== null) {
            $value .= $dot . $string->consumeAny(self::NUMBERS);
        }
        $e = $string->mayConsumeAny(['e', 'E']);
        if ($e !== null) {
            $value .= $e . $string->mayConsumeAny(['+', '-']) . $string->consumeAny(self::NUMBERS);
        }

        $value = ltrim($value, '+');
        if ($value === (string) (int) $value) {
            $value = (int) $value;
        } elseif ($value === (string) (float) $value) {
            $value = (float) $value;
        }

        return $value;
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
     * \Z	ASCII 26 (Control+Z); see note following the table
     * \\	A backslash (\) character
     * \%	A % character; see note following the table
     * \_	A _ character; see note following the table
     *
     * A ' inside a string quoted with ' may be written as ''.
     * A " inside a string quoted with " may be written as "".
     */
    private function unescapeString(string $string): string
    {
        ///
    }

}
