<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Parser\TokenType as T;
use SqlFtw\Platform\Platform;
use SqlFtw\Tests\Assert;

require '../bootstrap.php';

$settings = new ParserSettings(Platform::get(Platform::MYSQL, '5.7'));
$lexer = new Lexer($settings, true, true);

// BINARY_LITERAL
$tokens = $lexer->tokenizeAll(' 0b0101 ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::BINARY_LITERAL, '0101', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 7);

$tokens = $lexer->tokenizeAll(' b\'0101\' ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::BINARY_LITERAL, '0101', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' B\'0101\' ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::BINARY_LITERAL, '0101', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' b\'0102\' ');
Assert::invalidToken($tokens[1], T::VALUE | T::BINARY_LITERAL | T::INVALID, '~^Invalid binary literal~', 1);

$tokens = $lexer->tokenizeAll(' b\'0101 ');
Assert::invalidToken($tokens[1], T::VALUE | T::BINARY_LITERAL | T::INVALID, '~^Invalid binary literal~', 1);

// HEXADECIMAL_LITERAL
$tokens = $lexer->tokenizeAll(' 0x12AB ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::HEXADECIMAL_LITERAL, '12ab', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 7);

$tokens = $lexer->tokenizeAll(' x\'12AB\' ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::HEXADECIMAL_LITERAL, '12ab', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' X\'12AB\' ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::VALUE | T::HEXADECIMAL_LITERAL, '12ab', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' x\'12AG\' ');
Assert::invalidToken($tokens[1], T::VALUE | T::HEXADECIMAL_LITERAL | T::INVALID, '~^Invalid hexadecimal literal~', 1);

$tokens = $lexer->tokenizeAll(' x\'12AB ');
Assert::invalidToken($tokens[1], T::VALUE | T::HEXADECIMAL_LITERAL | T::INVALID, '~^Invalid hexadecimal literal~', 1);
