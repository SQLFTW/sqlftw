<?php

namespace AlterExecutor\Parser;

use SqlFtw\Sql\Dal\Set\SetCommand;
use SqlFtw\Sql\Scope;
use SqlFtw\Sql\Expression\ExpressionNode;
use Tester\Assert;

require '../../../bootstrap.php';

\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$session = Scope::get(Scope::SESSION);
$global = Scope::get(Scope::GLOBAL);


// scopes
$commands = $parser->parse('
    SET var_0 = \'aaa\',
    @var_1 = \'bbb\',
    SESSION var_2 = \'ccc\',
    @@session.var_3 = \'ddd\',
    @@var_4 = \'eee\',
    GLOBAL var_5 = \'fff\',
    @@global.var_6 = \'ggg\''
);
Assert::count(1, $commands);
Assert::type(SetCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Dal\Set\SetCommand $command */
$command = $commands[0];
Assert::type(SetCommand::class, $command);
$assignments = $command->getAssignments();
Assert::count(7, $assignments);
Assert::same('var_0', $assignments[0]->getVariable());
Assert::same('@var_1', $assignments[1]->getVariable());
Assert::same('var_2', $assignments[2]->getVariable());
Assert::same('var_3', $assignments[3]->getVariable());
Assert::same('var_4', $assignments[4]->getVariable());
Assert::same('var_5', $assignments[5]->getVariable());
Assert::same('var_6', $assignments[6]->getVariable());
Assert::same($session, $assignments[0]->getScope());
Assert::same($session, $assignments[1]->getScope());
Assert::same($session, $assignments[2]->getScope());
Assert::same($session, $assignments[3]->getScope());
Assert::same($session, $assignments[4]->getScope());
Assert::same($global, $assignments[5]->getScope());
Assert::same($global, $assignments[6]->getScope());


// values
$commands = $parser->parse('
    SET var_string1 = \'string\',
    var_string2 = "string",
    var_int = 1,
    var_float = 1.2,
    var_bool1 = TRUE,
    var_bool2 = FALSE,
    var_bool3 = ON,
    var_bool4 = OFF,
    var_expr1 = expr'
    // var_expr2 = (SELECT SUM(foo) FROM bar)  -- parser detects another statement
);
Assert::count(1, $commands);
Assert::type(SetCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Dal\Set\SetCommand $command */
$command = $commands[0];
Assert::type(SetCommand::class, $command);
$assignments = $command->getAssignments();
Assert::count(9, $assignments);
Assert::same('string', $assignments[0]->getExpression());
Assert::same('string', $assignments[1]->getExpression());
Assert::same(1, $assignments[2]->getExpression());
Assert::same(1.2, $assignments[3]->getExpression());
Assert::same(true, $assignments[4]->getExpression());
Assert::same(false, $assignments[5]->getExpression());
Assert::same(true, $assignments[6]->getExpression());
Assert::same(false, $assignments[7]->getExpression());
Assert::equal(new ExpressionNode('expr'), $assignments[8]->getExpression());
//Assert::equal(new ExpressionNode('(SELECT SUM(foo) FROM bar)'), $assignments[9]->getExpression());
