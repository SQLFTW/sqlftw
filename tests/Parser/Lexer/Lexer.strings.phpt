<?php declare(strict_types = 1);

namespace SqlFtw\Parser\Lexer;

use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\Mode;
use SqlFtw\Platform\Platform;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Tests\Assert;

require '../../bootstrap.php';

$settings = new PlatformSettings(Platform::get(Platform::MYSQL, '5.7'));
$lexer = new Lexer($settings, true, true);

// KEYWORD
$tokens = $lexer->tokenizeAll(' SELECT ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::KEYWORD | TokenType::RESERVED, 'SELECT', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 7);

// UNQUOTED_NAME
$tokens = $lexer->tokenizeAll(' foo ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::UNQUOTED_NAME, 'foo', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 4);

// DOUBLE_QUOTED_STRING
$tokens = $lexer->tokenizeAll(' "foo" ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::DOUBLE_QUOTED_STRING, 'foo', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);

Assert::exception(function () use ($lexer): void {
    $lexer->tokenizeAll(' "foo');
}, EndOfStringNotFoundException::class);

// with ANSI_QUOTES mode enabled
$settings->setMode($settings->getMode()->add(Mode::ANSI_QUOTES));
$tokens = $lexer->tokenizeAll(' "foo" ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::DOUBLE_QUOTED_STRING, 'foo', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);
$settings->setMode($settings->getMode()->remove(Mode::ANSI_QUOTES));

// SINGLE_QUOTED_STRING
$tokens = $lexer->tokenizeAll(" 'foo' ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, 'foo', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);

Assert::exception(function () use ($lexer): void {
    $lexer->tokenizeAll(" 'foo");
}, EndOfStringNotFoundException::class);

// doubling quotes
$tokens = $lexer->tokenizeAll(" 'fo''o' ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, "fo'o", 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);

// escaping quotes
$tokens = $lexer->tokenizeAll(" 'fo\\'o' ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, "fo'o", 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(" 'foo\\\\' ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, 'foo\\', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(" 'fo\\\\\\'o' ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, "fo\\'o", 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 10);

// with escaping disabled
$settings->setMode($settings->getMode()->add(Mode::NO_BACKSLASH_ESCAPES));
$tokens = $lexer->tokenizeAll(" 'foo\\' ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, 'foo\\', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 7);
$settings->setMode($settings->getMode()->remove(Mode::NO_BACKSLASH_ESCAPES));

// BACKTICK_QUOTED_STRING
$tokens = $lexer->tokenizeAll(' `foo` ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::BACKTICK_QUOTED_STRING, 'foo', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);

Assert::exception(function () use ($lexer): void {
    $lexer->tokenizeAll(' `foo');
}, EndOfStringNotFoundException::class);

// AT_VARIABLE
$tokens = $lexer->tokenizeAll(' @foo ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::AT_VARIABLE, '@foo', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

// not AT_VARIABLE
$tokens = $lexer->tokenizeAll(' foo@bar ');
Assert::count(5, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::UNQUOTED_NAME, 'foo', 1);
Assert::token($tokens[2], TokenType::SYMBOL | TokenType::OPERATOR, '@', 4);
Assert::token($tokens[3], TokenType::NAME | TokenType::UNQUOTED_NAME, 'bar', 5);
Assert::token($tokens[4], TokenType::WHITESPACE, ' ', 8);

// ENCODING_DEFINITION
//$tokens = $lexer->tokenizeAll(" _utf8'foo' ");
//Assert::count(1, $tokens);
//Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
//Assert::token($tokens[1], TokenType::NAME | TokenType::ENCODING_DEFINITION, '_utf8', 1);
//Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);
//Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, 'foo', 1);
//Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 12);
