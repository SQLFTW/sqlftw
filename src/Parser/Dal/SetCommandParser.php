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
use function substr;

class SetCommandParser
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\ExpressionParser */
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
     *   | [GLOBAL | SESSION | PERSIST]
     *       system_var_name = expr
     *   | [@@global. | @@session. | @@persist. | @@]
     *       system_var_name = expr
     */
    public function parseSet(TokenList $tokenList): SetCommand
    {
        $tokenList->consumeKeyword(Keyword::SET);

        $assignments = [];
        do {
            /** @var \SqlFtw\Sql\Scope $scope */
            $scope = $tokenList->mayConsumeKeywordEnum(Scope::class);
            if ($scope !== null) {
                $variable = $tokenList->consumeKeywordEnum(SystemVariable::class);
            } else {
                $variable = $tokenList->mayConsume(TokenType::AT_VARIABLE);
                if ($variable !== null) {
                    // @
                    $variable = $variable->value;
                    if ($variable === '@@global'
                        || $variable === '@@session'
                        || $variable === '@@persist'
                        || substr($variable, 0, 2)
                    ) {
                        // @@
                        if ($variable === '@@global') {
                            $scope = Scope::get(Scope::GLOBAL);
                            $variable = null;
                        } elseif ($variable === '@@persist') {
                            $scope = Scope::get(Scope::PERSIST);
                            $variable = null;
                        } elseif ($variable === '@@session') {
                            $scope = Scope::get(Scope::SESSION);
                            $variable = null;
                        } else {
                            $scope = Scope::get(Scope::SESSION);
                            $variable = substr($variable, 2);
                        }
                        if ($variable === null) {
                            $tokenList->consume(TokenType::DOT);
                            $variable = $tokenList->consumeName();
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

        return new SetCommand($assignments);
    }

}
