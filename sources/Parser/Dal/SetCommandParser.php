<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\InvalidValueException;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dal\Set\SetAssignment;
use SqlFtw\Sql\Dal\Set\SetCommand;
use SqlFtw\Sql\Dal\SystemVariable;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\Scope;
use function ltrim;
use function strpos;
use function strtolower;
use function strtoupper;

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
                $name = $tokenList->expectNameOrStringEnum(SystemVariable::class)->getValue();
                $variable = new QualifiedName($name);
            } else {
                $variableToken = $tokenList->get(TokenType::AT_VARIABLE);
                if ($variableToken !== null) {
                    // @
                    /** @var string $name */
                    $name = $variableToken->value;
                    if (strpos($name, '@@') === 0) {
                        // @@
                        $upper = strtoupper($name);
                        $lower = strtolower($name);
                        if ($upper === '@@GLOBAL') {
                            $scope = Scope::get(Scope::GLOBAL);
                            $name = null;
                        } elseif ($upper === '@@PERSIST') {
                            $scope = Scope::get(Scope::PERSIST);
                            $name = null;
                        } elseif ($upper === '@@PERSIST_ONLY') {
                            $scope = Scope::get(Scope::PERSIST_ONLY);
                            $name = null;
                        } elseif ($upper === '@@SESSION') {
                            $scope = Scope::get(Scope::SESSION);
                            $name = null;
                        } elseif (SystemVariable::isValid(ltrim($lower, '@'))) {
                            $scope = Scope::get(Scope::SESSION);
                            $name = (new SystemVariable(ltrim($lower, '@')))->getValue();
                        } else {
                            throw new InvalidValueException('System variable name', $tokenList);
                        }
                        if ($name === null) {
                            $tokenList->expect(TokenType::DOT);
                            $name = $tokenList->expectNameOrStringEnum(SystemVariable::class)->getValue();
                        }
                    }
                    $variable = new QualifiedName($name);
                } else {
                    // !@
                    $name = $tokenList->expectName();
                    if ($tokenList->hasSymbol('.')) {
                        $name2 = $tokenList->expectName();
                        $variable = new QualifiedName($name2, $name);
                    } else {
                        $variable = new QualifiedName($name);
                    }
                }
            }

            $operator = $tokenList->expectAnyOperator(Operator::EQUAL, Operator::ASSIGN);
            $expression = $this->expressionParser->parseExpression($tokenList);

            $assignments[] = new SetAssignment($variable, $expression, $scope, $operator);
        } while ($tokenList->hasSymbol(','));

        return new SetCommand($assignments);
    }

}
