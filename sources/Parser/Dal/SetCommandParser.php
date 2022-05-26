<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable SlevomatCodingStandard.ControlStructures.AssignmentInCondition

namespace SqlFtw\Parser\Dal;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dal\Set\SetAssignment;
use SqlFtw\Sql\Dal\Set\SetCommand;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\Scope;
use SqlFtw\Sql\Expression\SystemVariable;
use SqlFtw\Sql\Expression\UserVariable;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\MysqlVariable;
use function in_array;
use function strtoupper;
use function substr;

class SetCommandParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    /**
     * SET variable_assignment [, variable_assignment] ...
     *
     * variable_assignment:
     *     user_var_name = expr
     *   | param_name = expr
     *   | local_var_name = expr
     *   | {GLOBAL | @@GLOBAL.} system_var_name = expr
     *   | {PERSIST | @@PERSIST.} system_var_name = expr
     *   | {PERSIST_ONLY | @@PERSIST_ONLY.} system_var_name = expr
     *   | [LOCAL | SESSION | @@SESSION. | @@] system_var_name = expr
     */
    public function parseSet(TokenList $tokenList): SetCommand
    {
        $tokenList->expectKeyword(Keyword::SET);

        $assignments = [];
        do {
            if ($tokenList->hasKeyword(Keyword::LOCAL)) {
                $scope = Scope::get(Scope::SESSION);
            } else {
                $scope = $tokenList->getKeywordEnum(Scope::class);
            }
            if ($scope !== null) {
                // GLOBAL foo
                $name = $tokenList->expectNameOrStringEnum(MysqlVariable::class)->getValue();
                $variable = new SystemVariable($name, $scope);
            } elseif (($token = $tokenList->get(TokenType::AT_VARIABLE)) !== null) {
                $variableName = $token->value;
                if (in_array(strtoupper($variableName), ['@@SESSION', '@@GLOBAL', '@@PERSIST', '@@PERSIST_ONLY'], true)) {
                    // @@global.foo
                    $tokenList->expectSymbol('.');
                    $scope = Scope::get(substr($variableName, 2));
                    $variable = new SystemVariable($tokenList->expectName(), $scope);
                } elseif (substr($variableName, 0, 2) === '@@') {
                    // @@foo
                    $variable = new SystemVariable(substr($variableName, 2));
                } else {
                    // @foo
                    $variable = new UserVariable($variableName);
                }
            } else {
                // foo
                $name = $tokenList->expectName();
                if ($tokenList->hasSymbol('.')) {
                    $name2 = $tokenList->expectName();
                    $variable = new QualifiedName($name2, $name);
                } else {
                    $variable = new QualifiedName($name);
                }
            }

            $operator = $tokenList->expectAnyOperator(Operator::EQUAL, Operator::ASSIGN);
            $expression = $this->expressionParser->parseExpression($tokenList);

            $assignments[] = new SetAssignment($variable, $expression, $operator);
        } while ($tokenList->hasSymbol(','));

        return new SetCommand($assignments);
    }

}
