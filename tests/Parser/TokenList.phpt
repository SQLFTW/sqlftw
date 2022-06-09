<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Platform\Platform;
use SqlFtw\Tests\Assert;

require '../bootstrap.php';

$settings = new ParserSettings(Platform::get(Platform::MYSQL, '5.7'));

$ws = new Token(TokenType::WHITESPACE, 0, 'ws');
$comment = new Token(TokenType::COMMENT, 1, 'comment');
$value = new Token(TokenType::VALUE, 2, 'value');
$name = new Token(TokenType::NAME, 2, 'name');

$tokenList = new TokenList([$ws, $comment, $value, $ws, $comment, $name, $ws, $comment], $settings);
$tokenList->setAutoSkip(TokenType::WHITESPACE | TokenType::COMMENT);

getLast:
Assert::same($tokenList->getLast()->value, $ws->value);

$tokenList->resetPosition(1);
Assert::same($tokenList->getLast()->value, $ws->value);

$tokenList->resetPosition(2);
Assert::same($tokenList->getLast()->value, $ws->value);

$tokenList->resetPosition(3);
Assert::same($tokenList->getLast()->value, $value->value);

$tokenList->resetPosition(4);
Assert::same($tokenList->getLast()->value, $value->value);

$tokenList->resetPosition(5);
Assert::same($tokenList->getLast()->value, $value->value);

$tokenList->resetPosition(6);
Assert::same($tokenList->getLast()->value, $name->value);

$tokenList->resetPosition(7);
Assert::same($tokenList->getLast()->value, $name->value);

$tokenList->resetPosition(8);
Assert::same($tokenList->getLast()->value, $name->value);

$tokenList->resetPosition(9);
Assert::same($tokenList->getLast()->value, $name->value);
