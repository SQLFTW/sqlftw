<?php declare(strict_types = 1);

// phpcs:disable SlevomatCodingStandard.ControlStructures.NewWithParentheses.MissingParentheses
// phpcs:disable Generic.Formatting.DisallowMultipleStatements.SameLine

namespace SqlFtw\Parser;

use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Sql\EntityType;
use SqlFtw\Tests\Assert;

require '../bootstrap.php';

$platform = Platform::get(Platform::MYSQL, '5.7');
$session = new Session($platform);

$ws = new Token; $ws->type = TokenType::WHITESPACE; $ws->position = 0; $ws->value = 'ws';
$comment = new Token; $comment->type = TokenType::COMMENT; $comment->position = 1; $comment->value = 'comment';
$value = new Token; $value->type = TokenType::VALUE; $value->position = 2; $value->value = 'value';
$name = new Token; $name->type = TokenType::NAME; $name->position = 2; $name->value = 'name';

$tokenList = new TokenList([$ws, $comment, $value, $ws, $comment, $name, $ws, $comment], $platform, $session);
$tokenList->setAutoSkip(TokenType::WHITESPACE | TokenType::COMMENT);

getLast:
Assert::same($tokenList->getLast()->value, $ws->value);

$tokenList->rewind(1);
Assert::same($tokenList->getLast()->value, $ws->value);

$tokenList->rewind(2);
Assert::same($tokenList->getLast()->value, $ws->value);

$tokenList->rewind(3);
Assert::same($tokenList->getLast()->value, $value->value);

$tokenList->rewind(4);
Assert::same($tokenList->getLast()->value, $value->value);

$tokenList->rewind(5);
Assert::same($tokenList->getLast()->value, $value->value);

$tokenList->rewind(6);
Assert::same($tokenList->getLast()->value, $name->value);

$tokenList->rewind(7);
Assert::same($tokenList->getLast()->value, $name->value);

$tokenList->rewind(8);
Assert::same($tokenList->getLast()->value, $name->value);

$tokenList->rewind(9);
Assert::same($tokenList->getLast()->value, $name->value);


expectNonReservedName:
$tokenList = Assert::tokenList(' 1 ');
Assert::exception(static function () use ($tokenList): void {
    $tokenList->expectNonReservedName(EntityType::TABLE);
}, InvalidTokenException::class);

$tokenList = Assert::tokenList(' select ');
Assert::exception(static function () use ($tokenList): void {
    $tokenList->expectNonReservedName(EntityType::TABLE);
}, InvalidTokenException::class);

$tokenList = Assert::tokenList(' `select` ');
Assert::same($tokenList->expectNonReservedName(EntityType::TABLE), 'select');
