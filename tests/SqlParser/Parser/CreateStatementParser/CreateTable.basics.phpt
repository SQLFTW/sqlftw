<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use Tester\Assert;

require '../../../bootstrap.php';

// phpcs:disable
\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse(
    'CREATE TABLE `test` (
        `id` bigint
    )'
);
Assert::count(1, $commands);
Assert::type(CreateTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\CreateTableCommand $command */
$command = $commands[0];
Assert::same('test', $command->getName());
Assert::null($command->getEngine());
Assert::null($command->getCharset());
Assert::null($command->getCollation());
Assert::count(1, $command->getColumnList()->getColumns());
Assert::count(0, $command->getIndexList()->getIndexes());
Assert::count(0, $command->getConstraintList()->getForeignKeys());

$commands = $parser->parse(
    'CREATE TABLE `test` (
        `id` bigint
    ) ENGINE InnoDB DEFAULT CHARSET ascii COLLATE ascii_general_ci AUTO_INCREMENT 123'
);
Assert::count(1, $commands);
Assert::type(CreateTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\CreateTableCommand $command */
$command = $commands[0];
Assert::same('InnoDB', $command->getEngine());
Assert::same(Charset::get(Charset::ASCII), $command->getCharset());
Assert::same('ascii_general_ci', $command->getCollation());
Assert::same(123, $command->getAutoIncrement());

// parameter syntax with "="
$commands = $parser->parse(
    'CREATE TABLE `test` (
        `id` bigint
    ) ENGINE = InnoDB DEFAULT CHARSET = ascii COLLATE = ascii_general_ci AUTO_INCREMENT = 123'
);
Assert::count(1, $commands);
Assert::type(CreateTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\CreateTableCommand $command */
$command = $commands[0];
Assert::same('InnoDB', $command->getEngine());
Assert::same(Charset::get(Charset::ASCII), $command->getCharset());
Assert::same('ascii_general_ci', $command->getCollation());
Assert::same(123, $command->getAutoIncrement());
