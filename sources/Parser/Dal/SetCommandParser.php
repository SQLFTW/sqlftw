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
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dal\Set\SetAssignment;
use SqlFtw\Sql\Dal\Set\SetCommand;
use SqlFtw\Sql\Dal\SystemVariable;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Scope;
use function strpos;
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
     *   | {GLOBAL | @@GLOBAL.} system_var_name
     *   | {PERSIST | @@PERSIST.} system_var_name
     *   | {PERSIST_ONLY | @@PERSIST_ONLY.} system_var_name
     *   | [SESSION | @@SESSION. | @@] system_var_name
     *
     * @param TokenList $tokenList
     * @return SetCommand
     */
    public function parseSet(TokenList $tokenList): SetCommand
    {
        $tokenList->consumeKeyword(Keyword::SET);

        $assignments = [];
        do {
            $position = $tokenList->getPosition();
            /** @var Scope|null $scope */
            $scope = $tokenList->mayConsumeKeywordEnum(Scope::class);
            if ($scope !== null) {
                $variable = $tokenList->consumeNameOrStringEnum(SystemVariable::class)->getValue();
            } else {
                $variableToken = $tokenList->mayConsume(TokenType::AT_VARIABLE);
                $variable = null;
                if ($variableToken !== null) {
                    // @
                    /** @var string $variable */
                    $variable = $variableToken->value;
                    if (strpos($variable, '@@') === 0) {
                        // @@
                        $upper = strtoupper($variable);
                        if ($upper === '@@GLOBAL') {
                            $scope = Scope::get(Scope::GLOBAL);
                            $variable = null;
                        } elseif ($upper === '@@PERSIST') {
                            $scope = Scope::get(Scope::PERSIST);
                            $variable = null;
                        } elseif ($upper === '@@PERSIST_ONLY') {
                            $scope = Scope::get(Scope::PERSIST_ONLY);
                            $variable = null;
                        } elseif ($upper === '@@SESSION') {
                            $scope = Scope::get(Scope::SESSION);
                            $variable = null;
                        } else {
                            $scope = Scope::get(Scope::SESSION);
                            $tokenList->resetPosition($position);
                            $variable = $tokenList->consumeNameOrStringEnum(SystemVariable::class, '@')->getValue();
                        }
                        if ($variable === null) {
                            $tokenList->consume(TokenType::DOT);
                            $variable = $tokenList->consumeNameOrStringEnum(SystemVariable::class)->getValue();
                        }
                    }
                } else {
                    // !@
                    $variable = $tokenList->consumeName();
                }
            }

            $expression = $this->expressionParser->parseExpression($tokenList);

            $assignments[] = new SetAssignment($variable, $expression, $scope);
        } while ($tokenList->mayConsumeComma());
        $tokenList->expectEnd();

        return new SetCommand($assignments);
    }

}
