<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Parser\TokenType as T;
use SqlFtw\Platform\Platform;
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

$tokens = $lexer->tokenizeAll(' /* comment /* inside */ comment */ ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::BLOCK_COMMENT, '/* comment /* inside */ comment */', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 35);

// according to /suite/innodb/t/innodb_bug48024.test this seems to be a valid comment, but the test is buggy and does
// not really test what it seems to. MySQL does not terminate the comment, but does not produce any error.
// implementing correct behavior (unterminated comment error), like PostgreSQL does
$tokens = $lexer->tokenizeAll(' /*/ comment /*/ ');
Assert::count($tokens, 2);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::BLOCK_COMMENT | T::INVALID, '/*/ comment /*/ ', 1);

$tokens = $lexer->tokenizeAll(' /* comment ');
Assert::count($tokens, 2);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::BLOCK_COMMENT | T::INVALID, '/* comment ', 1);

// HINT_COMMENT
$tokens = $lexer->tokenizeAll(' /*+ comment */ ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::BLOCK_COMMENT | T::HINT_COMMENT, '/*+ comment */', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 15);

// OPTIONAL_COMMENT
$tokens = $lexer->tokenizeAll(' /*!90000 comment */ ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::BLOCK_COMMENT | T::OPTIONAL_COMMENT, '/*!90000 comment */', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 20);

$tokens = $lexer->tokenizeAll(' /*!2*/ ');
Assert::count($tokens, 3);
Assert::token($tokens[0], T::WHITESPACE, ' ', 0);
Assert::token($tokens[1], T::COMMENT | T::BLOCK_COMMENT | T::OPTIONAL_COMMENT | T::INVALID, '/*!2*/', 1);
Assert::token($tokens[2], T::WHITESPACE, ' ', 7);


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
