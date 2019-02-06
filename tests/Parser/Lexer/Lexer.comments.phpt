<?php declare(strict_types = 1);

namespace SqlFtw\Parser\Lexer;

use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\Platform;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Tests\Assert;

require '../../bootstrap.php';

$settings = new PlatformSettings(Platform::get(Platform::MYSQL, '5.7'));
$lexer = new Lexer($settings, true, true);

// BLOCK_COMMENT
$tokens = $lexer->tokenizeAll(' /* comment */ ');
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::BLOCK_COMMENT, '/* comment */', 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 14);

Assert::exception(function () use ($lexer): void {
    $lexer->tokenizeAll(' /* comment ');
}, EndOfCommentNotFoundException::class);

// DOUBLE_HYPHEN_COMMENT
$tokens = $lexer->tokenizeAll(' -- comment');
Assert::count(2, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::DOUBLE_HYPHEN_COMMENT, '-- comment', 1);

$tokens = $lexer->tokenizeAll(" -- comment\n ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::DOUBLE_HYPHEN_COMMENT, "-- comment\n", 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 12);

// DOUBLE_SLASH_COMMENT
$tokens = $lexer->tokenizeAll(' // comment');
Assert::count(2, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::DOUBLE_SLASH_COMMENT, '// comment', 1);

$tokens = $lexer->tokenizeAll(" // comment\n ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::DOUBLE_SLASH_COMMENT, "// comment\n", 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 12);

// HASH_COMMENT
$tokens = $lexer->tokenizeAll(' # comment');
Assert::count(2, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::HASH_COMMENT, '# comment', 1);

$tokens = $lexer->tokenizeAll(" # comment\n ");
Assert::count(3, $tokens);
Assert::token($tokens[0], TokenType::WHITESPACE, ' ', 0);
Assert::token($tokens[1], TokenType::COMMENT | TokenType::HASH_COMMENT, "# comment\n", 1);
Assert::token($tokens[2], TokenType::WHITESPACE, ' ', 11);
