<?php

namespace SqlFtw\Parser\Lexer;

use SqlFtw\Platform\Platform;
use SqlFtw\Platform\Settings;
use SqlFtw\Parser\TokenType;
use SqlFtw\Tests\Assert;

require '../../bootstrap.php';

$settings = new Settings(Platform::get(Platform::MYSQL, '5.7'));
$lexer = new Lexer($settings,true, true);

// NUMBER
$tokens = $lexer->tokenizeAll(' 123 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, 123, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 4);

$tokens = $lexer->tokenizeAll(' +123 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, 123, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

$tokens = $lexer->tokenizeAll(' -123 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, -123, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

$tokens = $lexer->tokenizeAll(' 123.456 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, 123.456, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' 123. ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, 123, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

$tokens = $lexer->tokenizeAll(' .456 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, 0.456, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 5);

$tokens = $lexer->tokenizeAll(' 1.23e4 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, 12300, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 7);

$tokens = $lexer->tokenizeAll(' 1.23e+4 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, 12300, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' 1.23e-4 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, 0.000123, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);

$tokens = $lexer->tokenizeAll(' 123.e4 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::NUMBER, 1230000, 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 7);

Assert::exception(function () use ($lexer) {
    $lexer->tokenizeAll(' 1.23e');
}, ExpectedTokenNotFoundException::class);
