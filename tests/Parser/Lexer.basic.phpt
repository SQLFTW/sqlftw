<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Parser\TokenType as T;
use SqlFtw\Platform\Platform;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Sql\Keyword;
use SqlFtw\Tests\Assert;

require '../bootstrap.php';

$settings = new PlatformSettings(Platform::get(Platform::MYSQL, '5.7'));
$lexer = new Lexer($settings, true, true);

// nothing
$tokens = $lexer->tokenizeAll('');
Assert::count($tokens, 0);

// WHITESPACE
$tokens = $lexer->tokenizeAll(' ');
Assert::count($tokens, 1);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);

$tokens = $lexer->tokenizeAll("\t\n\r");
Assert::count($tokens, 1);
Assert::token($tokens[0], T::WHITESPACE, "\t\n\r", 0);

// PLACEHOLDER
$tokens = $lexer->tokenizeAll(' ? ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::PLACEHOLDER, '?', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

// DOT
$tokens = $lexer->tokenizeAll(' . ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, '.', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

// COMMA
$tokens = $lexer->tokenizeAll(' , ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, ',', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

// DELIMITER
$tokens = $lexer->tokenizeAll(' ; ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::DELIMITER, ';', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

// DELIMITER_DEFINITION, SEMICOLON
$tokens = $lexer->tokenizeAll("DELIMITER ;;\n;");
Assert::count($tokens, 5);
Assert::token($tokens[0], T::KEYWORD | T::NAME | T::UNQUOTED_NAME, Keyword::DELIMITER, 0);
Assert::token($tokens[1], T::WHITESPACE, ' ', 9);
Assert::token($tokens[2], T::DELIMITER_DEFINITION, ';;', 10);
Assert::token($tokens[3], T::WHITESPACE, "\n", 12);
Assert::token($tokens[4], T::SYMBOL, ';', 13);

$tokens = $lexer->tokenizeAll('DELIMITER SELECT');
Assert::invalidToken($tokens[2], T::DELIMITER_DEFINITION | T::INVALID, '~^Delimiter can not be a reserved word~', 10);

// NULL
$tokens = $lexer->tokenizeAll(' NULL ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::KEYWORD | T::NAME | T::UNQUOTED_NAME | T::VALUE, 'NULL', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 5);

// BOOL
$tokens = $lexer->tokenizeAll(' TRUE ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::KEYWORD | T::NAME | T::UNQUOTED_NAME | T::VALUE, 'TRUE', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 5);

$tokens = $lexer->tokenizeAll(' FALSE ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::KEYWORD | T::NAME | T::UNQUOTED_NAME | T::VALUE, 'FALSE', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 6);

// uuid
$tokens = $lexer->tokenizeAll(' 3E11FA47-71CA-11E1-9E33-C80AA9429562 ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::UUID, '3E11FA47-71CA-11E1-9E33-C80AA9429562', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 37);

// parenthesis
$tokens = $lexer->tokenizeAll(' ( ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, '(', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' ) ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, ')', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' [ ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, '[', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' ] ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, ']', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' { ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, '{', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' } ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, '}', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

// OPERATOR
$tokens = $lexer->tokenizeAll(' := ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL | T::OPERATOR, ':=', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 3);

/*
$tokens = $lexer->tokenizeAll(' ?= ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL | T::OPERATOR, '?=', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 3);
*/
/*
$tokens = $lexer->tokenizeAll(' @= ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL | T::OPERATOR, '@=', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 3);
*/
