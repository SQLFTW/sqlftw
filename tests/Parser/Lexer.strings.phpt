<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Parser\TokenType as T;
use SqlFtw\Platform\Platform;
use SqlFtw\Parser\ParserSettings;
use SqlFtw\Sql\SqlMode;
use SqlFtw\Tests\Assert;

require '../bootstrap.php';

$settings = new ParserSettings(Platform::get(Platform::MYSQL, '5.7'));
$lexer = new Lexer($settings, true, true);


// OPERATOR
$tokens = $lexer->tokenizeAll(' AND ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME | T::OPERATOR, 'AND', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 4);

$tokens = $lexer->tokenizeAll(' and ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME | T::OPERATOR, 'and', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 4);


// RESERVED
$tokens = $lexer->tokenizeAll(' SELECT ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME, 'SELECT', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 7);

$tokens = $lexer->tokenizeAll(' select ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME, 'select', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 7);


// KEYWORD
$tokens = $lexer->tokenizeAll(' JOIN ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME, 'JOIN', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 5);

$tokens = $lexer->tokenizeAll(' join ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME, 'join', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 5);


// UNQUOTED_NAME
$tokens = $lexer->tokenizeAll(' NAME1 ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::NAME | T::UNQUOTED_NAME, 'NAME1', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 6);

$tokens = $lexer->tokenizeAll(' name1 ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::NAME | T::UNQUOTED_NAME, 'name1', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 6);


// DOUBLE_QUOTED_STRING
$tokens = $lexer->tokenizeAll(' "string1" ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::STRING | T::DOUBLE_QUOTED_STRING, 'string1', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 10);

// invalid
$tokens = $lexer->tokenizeAll(' "string1');
Assert::count($tokens, 2);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::STRING | T::DOUBLE_QUOTED_STRING | T::INVALID, '"string1', 1);

// with ANSI_QUOTES mode enabled
$settings->setMode($settings->getMode()->add(SqlMode::ANSI_QUOTES));
$tokens = $lexer->tokenizeAll(' "string1" ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::NAME | T::DOUBLE_QUOTED_STRING, 'string1', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 10);
$settings->setMode($settings->getMode()->remove(SqlMode::ANSI_QUOTES));


// SINGLE_QUOTED_STRING
$tokens = $lexer->tokenizeAll(" 'string1' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, 'string1', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 10);

// invalid
$tokens = $lexer->tokenizeAll(" 'string1");
Assert::count($tokens, 2);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING | T::INVALID, "'string1", 1);

// doubling quotes
$tokens = $lexer->tokenizeAll(" 'str''ing1' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, "str'ing1", 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 12);

// escaping quotes
$tokens = $lexer->tokenizeAll(" 'str\\'ing1' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, "str'ing1", 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 12);


$tokens = $lexer->tokenizeAll(" '\\\\' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, "\\", 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 5);

$tokens = $lexer->tokenizeAll(" 'string1\\\\' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, 'string1\\', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 12);

$tokens = $lexer->tokenizeAll(" 'str\\\\\\'ing1' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, "str\\'ing1", 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 14);

// with escaping disabled
$settings->setMode($settings->getMode()->add(SqlMode::NO_BACKSLASH_ESCAPES));
$tokens = $lexer->tokenizeAll(" 'string1\\' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, 'string1\\', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 11);
$settings->setMode($settings->getMode()->remove(SqlMode::NO_BACKSLASH_ESCAPES));


// BACKTICK_QUOTED_STRING
$tokens = $lexer->tokenizeAll(' `name1` ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::NAME | T::BACKTICK_QUOTED_STRING, 'name1', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 8);

// invalid
$tokens = $lexer->tokenizeAll(' `name1');
Assert::count($tokens, 2);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::NAME | T::BACKTICK_QUOTED_STRING | T::INVALID, '`name1', 1);


// N strings
$tokens = $lexer->tokenizeAll(" N'\\\\' ");
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, "\\", 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 6);


// AT_VARIABLE
$tokens = $lexer->tokenizeAll(' @var1 ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::NAME | T::AT_VARIABLE, '@var1', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 6);

$tokens = $lexer->tokenizeAll(' @`var1` ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::NAME | T::AT_VARIABLE, '@var1', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' @\'var1\' ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::NAME | T::AT_VARIABLE, '@var1', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' @"var1" ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::NAME | T::AT_VARIABLE, '@var1', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' name1@name2 ');
Assert::count($tokens, 4);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::NAME | T::UNQUOTED_NAME, 'name1', 1);
Assert::token($tokens[2], T::NAME | T::AT_VARIABLE, '@name2', 6);
Assert::token($tokens[3], T::WHITESPACE, ' ', 12);
