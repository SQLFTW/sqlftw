<?php declare(strict_types = 1);

namespace SqlFtw\Parser\Lexer;

use SqlFtw\Sql\Keyword;
use SqlFtw\Parser\TokenType;
use AlterExecutor\Tests\Assert;

require '../../bootstrap.php';

$lexer = new Lexer(true, true);

// nothing
$tokens = $lexer->tokenize('');
Assert::count(0, $tokens);

// WHITESPACE
$tokens = $lexer->tokenize(' ');
Assert::count(1, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);

$tokens = $lexer->tokenize("\t\n\r");
Assert::count(1, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, "\t\n\r", 0);

// BLOCK_COMMENT
$tokens = $lexer->tokenize(' /* comment */ ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::BLOCK_COMMENT, '/* comment */', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 14);

Assert::exception(function () use ($lexer) {
    $lexer->tokenize(' /* comment ');
}, ExpectedTokenNotFoundException::class);

// DOUBLE_HYPHEN_COMMENT
$tokens = $lexer->tokenize(' -- comment');
Assert::count(2, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::DOUBLE_HYPHEN_COMMENT, '-- comment', 1);

$tokens = $lexer->tokenize(" -- comment\n ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::DOUBLE_HYPHEN_COMMENT, '-- comment', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, "\n ", 11);

// DOUBLE_SLASH_COMMENT
$tokens = $lexer->tokenize(' // comment');
Assert::count(2, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::DOUBLE_SLASH_COMMENT, '// comment', 1);

$tokens = $lexer->tokenize(" // comment\n ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::DOUBLE_SLASH_COMMENT, '// comment', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, "\n ", 11);

// HASH_COMMENT
$tokens = $lexer->tokenize(' # comment');
Assert::count(2, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::HASH_COMMENT, '# comment', 1);

$tokens = $lexer->tokenize(" # comment\n ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::HASH_COMMENT, '# comment', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, "\n ", 10);

// VARIABLE_MARKER
$tokens = $lexer->tokenize(' ? ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::PLACEHOLDER, '?', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// DOT
$tokens = $lexer->tokenize(' . ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::DOT, '.', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// COMMA
$tokens = $lexer->tokenize(' , ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::COMMA, ',', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// DELIMITER
$tokens = $lexer->tokenize(' ; ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::DELIMITER, ';', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// DELIMITER_DEFINITION, SEMICOLON
$tokens = $lexer->tokenize('DELIMITER ;; ;');
Assert::count(5, $tokens);
Assert::token($tokens[0], TokenType::KEYWORD, Keyword::DELIMITER, 0);
Assert::token($tokens[1], TokenType::WHITESPACE, ' ', 9);
Assert::token($tokens[2], TokenType::SYMBOL | TokenType::DELIMITER_DEFINITION, ';;', 10);
Assert::token($tokens[3], TokenType::WHITESPACE, ' ', 12);
Assert::token($tokens[4], TokenType::SYMBOL | TokenType::SEMICOLON, ';', 13);

Assert::exception(function () use ($lexer) {
    $lexer->tokenize('DELIMITER foo');
}, ExpectedTokenNotFoundException::class);

// KEYWORD
$tokens = $lexer->tokenize(' SELECT ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::KEYWORD, 'SELECT', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 7);

// UNQUOTED_NAME
$tokens = $lexer->tokenize(' foo ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::UNQUOTED_NAME, 'foo', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 4);

// DOUBLE_QUOTED_STRING
$tokens = $lexer->tokenize(' "foo" ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::DOUBLE_QUOTED_STRING, 'foo', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);

Assert::exception(function () use ($lexer) {
    $lexer->tokenize(' "foo');
}, ExpectedTokenNotFoundException::class);

// SINGLE_QUOTED_STRING
$tokens = $lexer->tokenize(" 'foo' ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, 'foo', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);

Assert::exception(function () use ($lexer) {
    $lexer->tokenize(" 'foo");
}, ExpectedTokenNotFoundException::class);

// BACKTICK_QUOTED_STRING
$tokens = $lexer->tokenize(' `foo` ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::BACKTICK_QUOTED_STRING, 'foo', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);

Assert::exception(function () use ($lexer) {
    $lexer->tokenize(" `foo");
}, ExpectedTokenNotFoundException::class);

// LOCAL_VARIABLE
//$tokens = $lexer->tokenize('@foo');
//Assert::count(1, $tokens);
//Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
//Assert::token($tokens[1], TokenType::NAME | TokenType::LOCAL_VARIABLE, '@foo', 1);
//Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

// ENCODING_DEFINITION
//$tokens = $lexer->tokenize(" _utf8'foo' ");
//Assert::count(1, $tokens);
//Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
//Assert::token($tokens[1], TokenType::NAME | TokenType::ENCODING_DEFINITION, '_utf8', 1);
//Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);
//Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, 'foo', 1);
//Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 12);

// NULL
$tokens = $lexer->tokenize(' NULL ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::KEYWORD | TokenType::VALUE | TokenType::NULL, null, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

// BOOL
$tokens = $lexer->tokenize(' TRUE ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::KEYWORD | TokenType::VALUE | TokenType::BOOLEAN, true, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

$tokens = $lexer->tokenize(' FALSE ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::KEYWORD | TokenType::VALUE | TokenType::BOOLEAN, false, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);

// NUMBER
$tokens = $lexer->tokenize(' 123 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, 123, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 4);

$tokens = $lexer->tokenize(' +123 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, 123, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

$tokens = $lexer->tokenize(' -123 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, -123, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

$tokens = $lexer->tokenize(' 123.456 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, 123.456, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);

Assert::exception(function () use ($lexer) {
    $lexer->tokenize(" 123.");
}, ExpectedTokenNotFoundException::class);

$tokens = $lexer->tokenize(' 1.23e4 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, '1.23e4', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 7);

Assert::exception(function () use ($lexer) {
    $lexer->tokenize(" 1.23e");
}, ExpectedTokenNotFoundException::class);

// uuid
$tokens = $lexer->tokenize(' 3E11FA47-71CA-11E1-9E33-C80AA9429562 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::UUID, '3E11FA47-71CA-11E1-9E33-C80AA9429562', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 37);

// parenthesis
$tokens = $lexer->tokenize(' ( ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::LEFT_PARENTHESIS, '(', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenize(' ) ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::RIGHT_PARENTHESIS, ')', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenize(' [ ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::LEFT_SQUARE_BRACKET, '[', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenize(' ] ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::RIGHT_SQUARE_BRACKET, ']', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenize(' { ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::LEFT_CURLY_BRACKET, '{', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenize(' } ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::RIGHT_CURLY_BRACKET, '}', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// OPERATOR
$tokens = $lexer->tokenize(' := ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::OPERATOR, ':=', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 3);

$tokens = $lexer->tokenize(' ?= ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::OPERATOR, ':=', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 3);

$tokens = $lexer->tokenize(' @= ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::OPERATOR, ':=', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 3);
