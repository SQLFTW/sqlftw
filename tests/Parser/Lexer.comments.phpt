<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Parser\TokenType as T;
use SqlFtw\Platform\Platform;
use SqlFtw\Parser\ParserSettings;
use SqlFtw\Tests\Assert;

require '../bootstrap.php';

$settings = new ParserSettings(Platform::get(Platform::MYSQL, '5.7'));
$lexer = new Lexer($settings, true, true);

// BLOCK_COMMENT
$tokens = $lexer->tokenizeAll(' /* comment */ ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::BLOCK_COMMENT, '/* comment */', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 14);

Assert::exception(static function () use ($lexer): void {
    $lexer->tokenizeAll(' /* comment ');
}, LexerException::class, '~^End of comment not found~');

// HINT_COMMENT
$tokens = $lexer->tokenizeAll(' /*+ comment */ ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::BLOCK_COMMENT | T::HINT_COMMENT, '/*+ comment */', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 15);

// OPTIONAL_COMMENT
$tokens = $lexer->tokenizeAll(' /*!90000 comment */ ');
//Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::BLOCK_COMMENT | T::OPTIONAL_COMMENT, '/*!90000 comment */', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 20);



// DOUBLE_HYPHEN_COMMENT
$tokens = $lexer->tokenizeAll(' -- comment');
Assert::count($tokens, 2);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::DOUBLE_HYPHEN_COMMENT, '-- comment', 1);

$tokens = $lexer->tokenizeAll(" -- comment\n ");
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::DOUBLE_HYPHEN_COMMENT, "-- comment", 1);
Assert::token($tokens[2], T::WHITESPACE, "\n ", 11);

// DOUBLE_SLASH_COMMENT
$tokens = $lexer->tokenizeAll(' // comment');
Assert::count($tokens, 2);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::DOUBLE_SLASH_COMMENT, '// comment', 1);

$tokens = $lexer->tokenizeAll(" // comment\n ");
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::DOUBLE_SLASH_COMMENT, "// comment", 1);
Assert::token($tokens[2], T::WHITESPACE, "\n ", 11);

// HASH_COMMENT
$tokens = $lexer->tokenizeAll(' # comment');
Assert::count($tokens, 2);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::HASH_COMMENT, '# comment', 1);

$tokens = $lexer->tokenizeAll(" # comment\n ");
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::HASH_COMMENT, "# comment", 1);
Assert::token($tokens[2], T::WHITESPACE, "\n ", 10);
