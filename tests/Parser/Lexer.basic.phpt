<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Parser\TokenType as T;
use SqlFtw\Sql\Keyword;
use SqlFtw\Tests\Assert;

require '../bootstrap.php';


// nothing
Assert::tokens('', 0);

// WHITESPACE
$tokens = Assert::tokens(' ', 1);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);

$tokens = Assert::tokens("\t\n\r", 1);
Assert::token($tokens[0], T::WHITESPACE, "\t\n\r", 0);

// PLACEHOLDER
$tokens = Assert::tokens(' ? ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL | T::PLACEHOLDER, '?', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

// DOT
$tokens = Assert::tokens(' . ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, '.', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

// COMMA
$tokens = Assert::tokens(' , ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, ',', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

// DELIMITER
$tokens = Assert::tokens(' ; ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::DELIMITER, ';', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

// DELIMITER_DEFINITION, SEMICOLON
$tokens = Assert::tokens("DELIMITER ;;\n;", 5);
Assert::token($tokens[0], T::KEYWORD | T::NAME | T::UNQUOTED_NAME, Keyword::DELIMITER, 0);
Assert::token($tokens[1], T::WHITESPACE, ' ', 9);
Assert::token($tokens[2], T::DELIMITER_DEFINITION, ';;', 10);
Assert::token($tokens[3], T::WHITESPACE, "\n", 12);
Assert::token($tokens[4], T::SYMBOL, ';', 13);

$tokens = Assert::tokens('DELIMITER SELECT', 3);
Assert::invalidToken($tokens[2], T::DELIMITER_DEFINITION | T::INVALID, '~^Delimiter can not be a reserved word~', 10);

// NULL
$tokens = Assert::tokens(' NULL ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::KEYWORD | T::NAME | T::UNQUOTED_NAME | T::RESERVED, 'NULL', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 5);

// uuid
$tokens = Assert::tokens(' 3E11FA47-71CA-11E1-9E33-C80AA9429562 ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::UUID, '3E11FA47-71CA-11E1-9E33-C80AA9429562', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 37);

// parenthesis
$tokens = Assert::tokens(' ( ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, '(', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

$tokens = Assert::tokens(' ) ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, ')', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

$tokens = Assert::tokens(' [ ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, '[', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

$tokens = Assert::tokens(' ] ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, ']', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

$tokens = Assert::tokens(' { ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, '{', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

$tokens = Assert::tokens(' } ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL, '}', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 2);

// OPERATOR
$tokens = Assert::tokens(' := ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL | T::OPERATOR, ':=', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 3);

$tokens = Assert::tokens(' OR ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME | T::OPERATOR, 'OR', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 3);

/*
$tokens = Assert::tokens(' ?= ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL | T::OPERATOR, '?=', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 3);
*/
/*
$tokens = Assert::tokens(' @= ', 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::SYMBOL | T::OPERATOR, '@=', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 3);
*/
