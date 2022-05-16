<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

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
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);

$tokens = $lexer->tokenizeAll("\t\n\r");
Assert::count($tokens, 1);
Assert::token($tokens[0], TokenType::WHITESPACE, "\t\n\r", 0);

// PLACEHOLDER
$tokens = $lexer->tokenizeAll(' ? ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::PLACEHOLDER, '?', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// DOT
$tokens = $lexer->tokenizeAll(' . ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL, '.', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// COMMA
$tokens = $lexer->tokenizeAll(' , ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL, ',', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// DELIMITER
$tokens = $lexer->tokenizeAll(' ; ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::DELIMITER, ';', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// DELIMITER_DEFINITION, SEMICOLON
$tokens = $lexer->tokenizeAll("DELIMITER ;;\n;");
Assert::count($tokens, 5);
Assert::token($tokens[0], TokenType::KEYWORD, Keyword::DELIMITER, 0);
Assert::token($tokens[1], TokenType::WHITESPACE, ' ', 9);
Assert::token($tokens[2], TokenType::SYMBOL | TokenType::DELIMITER_DEFINITION, ';;', 10);
Assert::token($tokens[3], TokenType::WHITESPACE, "\n", 12);
Assert::token($tokens[4], TokenType::SYMBOL, ';', 13);

$tokens = $lexer->tokenizeAll('DELIMITER SELECT');
Assert::invalidToken($tokens[2], TokenType::INVALID, '~^Delimiter can not be a reserved word~', 10);

// NULL
$tokens = $lexer->tokenizeAll(' NULL ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::KEYWORD | TokenType::VALUE, 'NULL', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

// BOOL
$tokens = $lexer->tokenizeAll(' TRUE ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::KEYWORD | TokenType::VALUE, 'TRUE', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

$tokens = $lexer->tokenizeAll(' FALSE ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::KEYWORD | TokenType::VALUE, 'FALSE', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);

// uuid
$tokens = $lexer->tokenizeAll(' 3E11FA47-71CA-11E1-9E33-C80AA9429562 ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::UUID, '3E11FA47-71CA-11E1-9E33-C80AA9429562', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 37);

// parenthesis
$tokens = $lexer->tokenizeAll(' ( ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::LEFT_PARENTHESIS, '(', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' ) ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::RIGHT_PARENTHESIS, ')', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' [ ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::LEFT_SQUARE_BRACKET, '[', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' ] ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::RIGHT_SQUARE_BRACKET, ']', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' { ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL, '{', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' } ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL, '}', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// OPERATOR
$tokens = $lexer->tokenizeAll(' := ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::OPERATOR, ':=', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 3);

/*
$tokens = $lexer->tokenizeAll(' ?= ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::OPERATOR, '?=', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 3);
*/
/*
$tokens = $lexer->tokenizeAll(' @= ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::OPERATOR, '@=', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 3);
*/
