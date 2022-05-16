<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Platform\Mode;
use SqlFtw\Platform\Platform;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Tests\Assert;

require '../bootstrap.php';

$settings = new PlatformSettings(Platform::get(Platform::MYSQL, '5.7'));
$lexer = new Lexer($settings, true, true);

// KEYWORD
$tokens = $lexer->tokenizeAll(' SELECT ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::KEYWORD | TokenType::RESERVED, 'SELECT', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 7);

// UNQUOTED_NAME
$tokens = $lexer->tokenizeAll(' name1 ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::UNQUOTED_NAME, 'name1', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);

// DOUBLE_QUOTED_STRING
$tokens = $lexer->tokenizeAll(' "string1" ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::DOUBLE_QUOTED_STRING, 'string1', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 10);

Assert::exception(static function () use ($lexer): void {
    $lexer->tokenizeAll(' "string1');
}, LexerException::class, '~^End of string not found~');

// with ANSI_QUOTES mode enabled
$settings->setMode($settings->getMode()->add(Mode::ANSI_QUOTES));
$tokens = $lexer->tokenizeAll(' "string1" ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::DOUBLE_QUOTED_STRING, 'string1', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 10);
$settings->setMode($settings->getMode()->remove(Mode::ANSI_QUOTES));

// SINGLE_QUOTED_STRING
$tokens = $lexer->tokenizeAll(" 'string1' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, 'string1', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 10);

Assert::exception(static function () use ($lexer): void {
    $lexer->tokenizeAll(" 'string1");
}, LexerException::class, '~^End of string not found~');

// doubling quotes
$tokens = $lexer->tokenizeAll(" 'str''ing1' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, "str'ing1", 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 12);

// escaping quotes
$tokens = $lexer->tokenizeAll(" 'str\\'ing1' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, "str'ing1", 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 12);

$tokens = $lexer->tokenizeAll(" 'string1\\\\' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, 'string1\\', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 12);

$tokens = $lexer->tokenizeAll(" 'str\\\\\\'ing1' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, "str\\'ing1", 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 14);

// with escaping disabled
$settings->setMode($settings->getMode()->add(Mode::NO_BACKSLASH_ESCAPES));
$tokens = $lexer->tokenizeAll(" 'string1\\' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, 'string1\\', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 11);
$settings->setMode($settings->getMode()->remove(Mode::NO_BACKSLASH_ESCAPES));

// BACKTICK_QUOTED_STRING
$tokens = $lexer->tokenizeAll(' `name1` ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::BACKTICK_QUOTED_STRING, 'name1', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);

Assert::exception(static function () use ($lexer): void {
    $lexer->tokenizeAll(' `name1');
}, LexerException::class, '~^End of string not found~');


// AT_VARIABLE
$tokens = $lexer->tokenizeAll(' @var1 ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::AT_VARIABLE, '@var1', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);

$tokens = $lexer->tokenizeAll(' @`var1` ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::AT_VARIABLE, '@var1', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' @\'var1\' ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::AT_VARIABLE, '@var1', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' @"var1" ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::AT_VARIABLE, '@var1', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' name1@name2 ');
Assert::count($tokens, 4);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::UNQUOTED_NAME, 'name1', 1);
Assert::token($tokens[2], TokenType::NAME | TokenType::AT_VARIABLE, '@name2', 6);
Assert::token($tokens[3], TokenType::WHITESPACE, ' ', 12);


// CHARSET_INTRODUCER
$tokens = $lexer->tokenizeAll(" _utf8'string1' ");
Assert::count($tokens, 4);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::NAME | TokenType::CHARSET_INTRODUCER, 'utf8', 1);
Assert::token($tokens[2], TokenType::VALUE | TokenType::STRING | TokenType::SINGLE_QUOTED_STRING, 'string1', 6);
Assert::token($tokens[3], TokenType::WHITESPACE, ' ', 15);
