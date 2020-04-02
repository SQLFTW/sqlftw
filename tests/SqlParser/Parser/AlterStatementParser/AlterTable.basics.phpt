<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Parser\Lexer\Lexer;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserFactory;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableAlgorithm;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableLock;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use Tester\Assert;

require '../../../bootstrap.php';

// phpcs:disable
\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse(
    'ALTER TABLE `test`
    ADD `id` bigint,
    ADD COLUMN `uid` bigint'
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];
Assert::same('test', $command->getName());
Assert::null($command->getEngine());
Assert::null($command->getCharset());
Assert::null($command->getCollation());
Assert::same(AlterTableAlgorithm::get(AlterTableAlgorithm::DEFAULT), $command->getAlgorithm());
Assert::same(AlterTableLock::get(AlterTableLock::DEFAULT), $command->getLock());
Assert::count(2, $command->getColumnList()->getAddedColumns());
Assert::count(0, $command->getColumnList()->getDroppedColumns());
Assert::count(0, $command->getIndexList()->getIndexes());
Assert::count(0, $command->getIndexList()->getDroppedIndexes());
Assert::count(0, $command->getConstraintList()->getForeignKeys());
Assert::count(0, $command->getConstraintList()->getDroppedForeignKeys());

// parameters
$commands = $parser->parse(
    'ALTER TABLE `test`
    ADD `id` bigint,
    ENGINE InnoDB,
    DEFAULT CHARSET ascii,
    COLLATE ascii_general_ci,
    AUTO_INCREMENT 123,
    ALGORITHM INPLACE,
    LOCK NONE'
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];
Assert::same('InnoDB', $command->getEngine());
Assert::same(Charset::get(Charset::ASCII), $command->getCharset());
Assert::same('ascii_general_ci', $command->getCollation());
Assert::same(123, $command->getAutoIncrement());
Assert::same(AlterTableAlgorithm::get(AlterTableAlgorithm::INPLACE), $command->getAlgorithm());
Assert::same(AlterTableLock::get(AlterTableLock::NONE), $command->getLock());

// parameter syntax with "="
$commands = $parser->parse(
    'ALTER TABLE `test`
    ADD `id` bigint,
    ENGINE = InnoDB,
    DEFAULT CHARSET = ascii,
    COLLATE = ascii_general_ci,
    AUTO_INCREMENT = 123,
    ALGORITHM = INPLACE,
    LOCK = NONE'
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];
Assert::same('InnoDB', $command->getEngine());
Assert::same(Charset::get(Charset::ASCII), $command->getCharset());
Assert::same('ascii_general_ci', $command->getCollation());
Assert::same(123, $command->getAutoIncrement());
Assert::same(AlterTableAlgorithm::get(AlterTableAlgorithm::INPLACE), $command->getAlgorithm());
Assert::same(AlterTableLock::get(AlterTableLock::NONE), $command->getLock());
