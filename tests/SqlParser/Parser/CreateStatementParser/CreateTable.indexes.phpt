<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use SqlFtw\Sql\Ddl\Table\Index\IndexAlgorithm;
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;
use SqlFtw\Sql\Ddl\Table\Index\IndexType;
use Tester\Assert;

require '../../../bootstrap.php';

// phpcs:disable
\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse(
    'CREATE TABLE `test` (
        `id` bigint,
        `foo` char(10),
        `bar` char(20),
        PRIMARY KEY (`id`),
        UNIQUE KEY `key2` (`foo`(5), `bar`(10)),
        KEY `key3` (`bar`) USING HASH
    )'
);
Assert::count(1, $commands);
Assert::type(CreateTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\CreateTableCommand $command */
$command = $commands[0];
$indexes = $command->getIndexList()->getIndexes();

// name
Assert::same(IndexDefinition::PRIMARY_KEY_NAME, $indexes[0]->getName());
Assert::same('key2', $indexes['key2']->getName());
Assert::same('key3', $indexes['key3']->getName());

// type
Assert::same(IndexType::get(IndexType::PRIMARY), $indexes[0]->getType());
Assert::same(IndexType::get(IndexType::UNIQUE), $indexes['key2']->getType());
Assert::same(IndexType::get(IndexType::SECONDARY), $indexes['key3']->getType());

// algorithm
Assert::same(IndexAlgorithm::get(IndexAlgorithm::BTREE), $indexes[0]->getOptions());
Assert::same(IndexAlgorithm::get(IndexAlgorithm::BTREE), $indexes['key2']->getOptions());
Assert::same(IndexAlgorithm::get(IndexAlgorithm::HASH), $indexes['key3']->getOptions());

// column names
Assert::same(['id'], $indexes[0]->getColumnNames());
Assert::same(['foo', 'bar'], $indexes['key2']->getColumnNames());
Assert::same(['bar'], $indexes['key3']->getColumnNames());

// columns
$columns = $indexes[0]->getColumns();
Assert::count(1, $columns);
Assert::same('id', $columns['id']->getName());
Assert::same(null, $columns['id']->getLength());

$columns = $indexes['key2']->getColumns();
Assert::count(2, $columns);
Assert::same('foo', $columns['foo']->getName());
Assert::same('bar', $columns['bar']->getName());
Assert::same(5, $columns['foo']->getLength());
Assert::same(10, $columns['bar']->getLength());

$columns = $indexes['key3']->getColumns();
Assert::same('bar', $columns['bar']->getName());
Assert::same(null, $columns['bar']->getLength());
