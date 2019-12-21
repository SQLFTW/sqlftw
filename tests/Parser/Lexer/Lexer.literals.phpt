<?php declare(strict_types = 1);

namespace SqlFtw\Parser\Lexer;

use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\Platform;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Tests\Assert;

require '../../bootstrap.php';

$settings = new PlatformSettings(Platform::get(Platform::MYSQL, '5.7'));
$lexer = new Lexer($settings, true, true);
rd(1);
// BINARY_LITERAL
$tokens = $lexer->tokenizeAll(' 0b0101 ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::BINARY_LITERAL, '0101', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 7);
rd(2);
$tokens = $lexer->tokenizeAll(' b\'0101\' ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::BINARY_LITERAL, '0101', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);
rd(3);
$tokens = $lexer->tokenizeAll(' B\'0101\' ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::BINARY_LITERAL, '0101', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);
rd(4);
Assert::exception(static function () use ($lexer): void {
    $lexer->tokenizeAll(' b\'0102\' ');
}, ExpectedTokenNotFoundException::class);
rd(5);
Assert::exception(static function () use ($lexer): void {
    $lexer->tokenizeAll(' b\'0101 ');
}, ExpectedTokenNotFoundException::class);
rd(6);

// HEXADECIMAL_LITERAL
$tokens = $lexer->tokenizeAll(' 0x12AB ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::HEXADECIMAL_LITERAL, '12ab', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 7);
rd(7);
$tokens = $lexer->tokenizeAll(' x\'12AB\' ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::HEXADECIMAL_LITERAL, '12ab', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);
rd(7);
$tokens = $lexer->tokenizeAll(' X\'12AB\' ');
Assert::count($tokens, 3);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::VALUE | TokenType::HEXADECIMAL_LITERAL, '12ab', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 8);
rd(8);
Assert::exception(static function () use ($lexer): void {
    $lexer->tokenizeAll(' x\'12A\' ');
}, ExpectedTokenNotFoundException::class);
rd(9);
Assert::exception(static function () use ($lexer): void {
    $lexer->tokenizeAll(' x\'12AB ');
}, ExpectedTokenNotFoundException::class);
