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
use SqlFtw\Sql\Keyword;

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
     *   | {GLOBAL | @@GLOBAL. | global.} system_var_name = expr
     *   | {PERSIST | @@PERSIST. | persist.} system_var_name = expr
     *   | {PERSIST_ONLY | @@PERSIST_ONLY. | persist_only.} system_var_name = expr
     *   | [SESSION | @@SESSION. | session. | @@] system_var_name = expr
     *   | [LOCAL | @@LOCAL | local. | @@] system_var_name = expr -- alias for SESSION
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
            $tokenList->passSymbol('.');

            if ($scope !== null) {
                // GLOBAL foo
                $name = $tokenList->expectNonReservedNameOrString();
                if ($tokenList->hasSymbol('.')) {
                    $name .= '.' . $tokenList->expectName();
                }
                $variable = new SystemVariable($name, $scope);
            } elseif (($token = $tokenList->get(TokenType::AT_VARIABLE)) !== null) {
                // @foo, @@foo...
                $variable = $this->expressionParser->parseAtVariable($tokenList, $token->value);
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
            $expression = $this->expressionParser->parseAssignExpression($tokenList);

            $assignments[] = new SetAssignment($variable, $expression, $operator);
        } while ($tokenList->hasSymbol(','));

        return new SetCommand($assignments);
    }

}
