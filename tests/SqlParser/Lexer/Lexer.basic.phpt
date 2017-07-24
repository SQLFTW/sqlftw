<?php

namespace SqlFtw\Parser\Lexer;

use SqlFtw\Platform\Platform;
use SqlFtw\Platform\Settings;
use SqlFtw\Sql\Keyword;
use SqlFtw\Parser\TokenType;
use SqlFtw\Tests\Assert;

require '../../bootstrap.php';

$settings = new Settings(Platform::get(Platform::MYSQL, '5.7'));
$lexer = new Lexer($settings,true, true);

// nothing
$tokens = $lexer->tokenizeAll('');
Assert::count(0, $tokens);

// WHITESPACE
$tokens = $lexer->tokenizeAll(' ');
Assert::count(1, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);

$tokens = $lexer->tokenizeAll("\t\n\r");
Assert::count(1, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, "\t\n\r", 0);

// VARIABLE_MARKER
$tokens = $lexer->tokenizeAll(' ? ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::PLACEHOLDER, '?', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// DOT
$tokens = $lexer->tokenizeAll(' . ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::DOT, '.', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// COMMA
$tokens = $lexer->tokenizeAll(' , ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::COMMA, ',', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// DELIMITER
$tokens = $lexer->tokenizeAll(' ; ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::DELIMITER, ';', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// DELIMITER_DEFINITION, SEMICOLON
$tokens = $lexer->tokenizeAll('DELIMITER ;; ;');
Assert::count(5, $tokens);
Assert::token($tokens[0], TokenType::KEYWORD, Keyword::DELIMITER, 0);
Assert::token($tokens[1], TokenType::WHITESPACE, ' ', 9);
Assert::token($tokens[2], TokenType::SYMBOL | TokenType::DELIMITER_DEFINITION, ';;', 10);
Assert::token($tokens[3], TokenType::WHITESPACE, ' ', 12);
Assert::token($tokens[4], TokenType::SYMBOL | TokenType::SEMICOLON, ';', 13);

Assert::exception(function () use ($lexer) {
    $lexer->tokenizeAll('DELIMITER foo');
}, ExpectedTokenNotFoundException::class);

// NULL
$tokens = $lexer->tokenizeAll(' NULL ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::KEYWORD | TokenType::VALUE, null, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

// BOOL
$tokens = $lexer->tokenizeAll(' TRUE ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::KEYWORD | TokenType::VALUE, true, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

$tokens = $lexer->tokenizeAll(' FALSE ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::KEYWORD | TokenType::VALUE, false, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 6);

// uuid
$tokens = $lexer->tokenizeAll(' 3E11FA47-71CA-11E1-9E33-C80AA9429562 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::UUID, '3E11FA47-71CA-11E1-9E33-C80AA9429562', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 37);

// parenthesis
$tokens = $lexer->tokenizeAll(' ( ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::LEFT_PARENTHESIS, '(', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' ) ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::RIGHT_PARENTHESIS, ')', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' [ ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::LEFT_SQUARE_BRACKET, '[', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' ] ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::RIGHT_SQUARE_BRACKET, ']', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' { ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::LEFT_CURLY_BRACKET, '{', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

$tokens = $lexer->tokenizeAll(' } ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::RIGHT_CURLY_BRACKET, '}', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 2);

// OPERATOR
$tokens = $lexer->tokenizeAll(' := ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::OPERATOR, ':=', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 3);

/*
$tokens = $lexer->tokenizeAll(' ?= ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::OPERATOR, '?=', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 3);
*/
/*
$tokens = $lexer->tokenizeAll(' @= ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::SYMBOL | TokenType::OPERATOR, '@=', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 3);
*/
