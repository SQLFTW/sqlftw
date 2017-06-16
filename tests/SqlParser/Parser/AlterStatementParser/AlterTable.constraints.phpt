<?php

namespace AlterExecutor\Parser;

use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyAction;
use Tester\Assert;

require '../../../bootstrap.php';

\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse(
    'ALTER TABLE `test`
        ADD CONSTRAINT `fk1` FOREIGN KEY (`id`) REFERENCES `test2` (`id2`),
        ADD CONSTRAINT `fk2` FOREIGN KEY (`foo`, `bar`) REFERENCES `baz`.`taz` (`foo2`, `bar2`) ON UPDATE RESTRICT ON DELETE CASCADE,
        ADD CONSTRAINT `fk3` FOREIGN KEY (`bar`) REFERENCES `test2` (`bar2`) ON UPDATE NO ACTION ON DELETE SET NULL,
        ADD FOREIGN KEY (`baz`) REFERENCES `test2` (`baz2`) ON UPDATE RESTRICT ON DELETE CASCADE
    '
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];
$constraints = $command->getConstraintList()->getForeignKeys();
Assert::count(4, $constraints);

// name
Assert::same('fk1', $constraints['fk1']->getName());
Assert::same('fk2', $constraints['fk2']->getName());
Assert::same('fk3', $constraints['fk3']->getName());
Assert::null($constraints[0]->getName());

// columns
Assert::same(['id'], $constraints['fk1']->getColumns());
Assert::same(['foo', 'bar'], $constraints['fk2']->getColumns());
Assert::same(['bar'], $constraints['fk3']->getColumns());
Assert::same(['baz'], $constraints[0]->getColumns());

// source database
Assert::null($constraints['fk1']->getSourceTable()->getDatabaseName());
Assert::same('baz', $constraints['fk2']->getSourceTable()->getDatabaseName());
Assert::null($constraints['fk3']->getSourceTable()->getDatabaseName());
Assert::null($constraints[0]->getSourceTable()->getDatabaseName());

// source table
Assert::same('test2', $constraints['fk1']->getSourceTable()->getName());
Assert::same('taz', $constraints['fk2']->getSourceTable()->getName());
Assert::same('test2', $constraints['fk3']->getSourceTable()->getName());
Assert::same('test2', $constraints[0]->getSourceTable()->getName());

// source columns
Assert::same(['id2'], $constraints['fk1']->getSourceColumns());
Assert::same(['foo2', 'bar2'], $constraints['fk2']->getSourceColumns());
Assert::same(['bar2'], $constraints['fk3']->getSourceColumns());
Assert::same(['baz2'], $constraints[0]->getSourceColumns());

// actions
Assert::same(ForeignKeyAction::get(ForeignKeyAction::NO_ACTION), $constraints['fk1']->getOnUpdate());
Assert::same(ForeignKeyAction::get(ForeignKeyAction::NO_ACTION), $constraints['fk1']->getOnDelete());
Assert::same(ForeignKeyAction::get(ForeignKeyAction::RESTRICT), $constraints['fk2']->getOnUpdate());
Assert::same(ForeignKeyAction::get(ForeignKeyAction::CASCADE), $constraints['fk2']->getOnDelete());
Assert::same(ForeignKeyAction::get(ForeignKeyAction::NO_ACTION), $constraints['fk3']->getOnUpdate());
Assert::same(ForeignKeyAction::get(ForeignKeyAction::SET_NULL), $constraints['fk3']->getOnDelete());
Assert::same(ForeignKeyAction::get(ForeignKeyAction::RESTRICT), $constraints[0]->getOnUpdate());
Assert::same(ForeignKeyAction::get(ForeignKeyAction::CASCADE), $constraints[0]->getOnDelete());


$commands = $parser->parse(
    'ALTER TABLE `test`
        DROP FOREIGN KEY `fk1`
    '
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];
$constraints = $command->getConstraintList()->getDroppedForeignKeys();

Assert::same('fk1', $constraints['fk1']->getName());
