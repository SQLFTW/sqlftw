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
use SqlFtw\Parser\ParserException;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dal\Set\ResetPersistCommand;
use SqlFtw\Sql\Dal\Set\SetAssignment;
use SqlFtw\Sql\Dal\Set\SetCharacterSetCommand;
use SqlFtw\Sql\Dal\Set\SetNamesCommand;
use SqlFtw\Sql\Dal\Set\SetVariablesCommand;
use SqlFtw\Sql\Entity;
use SqlFtw\Sql\Expression\DefaultLiteral;
use SqlFtw\Sql\Expression\EnumValueLiteral;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\Scope;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Expression\SystemVariable;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\MysqlVariable;
use function is_array;

class SetCommandsParser
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
    public function parseSet(TokenList $tokenList): SetVariablesCommand
    {
        $tokenList->expectKeyword(Keyword::SET);

        $assignments = $this->parseAssignments($tokenList);

        return new SetVariablesCommand($assignments);
    }

    /**
     * SET {CHARACTER SET | CHARSET}
     *     {'charset_name' | DEFAULT}
     */
    public function parseSetCharacterSet(TokenList $tokenList): SetCharacterSetCommand
    {
        $tokenList->expectKeyword(Keyword::SET);
        $keyword = $tokenList->expectAnyKeyword(Keyword::CHARACTER, Keyword::CHARSET);
        if ($keyword === Keyword::CHARACTER) {
            $tokenList->expectKeyword(Keyword::SET);
        }

        if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
            $charset = null;
        } else {
            $charset = $tokenList->expectCharsetName();
        }

        $assignments = [];
        if ($tokenList->hasSymbol(',')) {
            $assignments = $this->parseAssignments($tokenList);
        }

        return new SetCharacterSetCommand($charset, $assignments);
    }

    /**
     * SET NAMES {'charset_name' [COLLATE 'collation_name'] | DEFAULT}
     *     [, variable_name [=] value]
     */
    public function parseSetNames(TokenList $tokenList): SetNamesCommand
    {
        $tokenList->expectKeywords(Keyword::SET, Keyword::NAMES);

        $collation = null;
        if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
            $charset = new DefaultLiteral();
        } else {
            $charset = $tokenList->expectCharsetName();

            if ($tokenList->hasKeyword(Keyword::COLLATE)) {
                $collation = $tokenList->expectCollationName();
            }
        }

        $assignments = [];
        if ($tokenList->hasSymbol(',')) {
            $assignments = $this->parseAssignments($tokenList);
        }

        return new SetNamesCommand($charset, $collation, $assignments);
    }

    /**
     * RESET PERSIST [[IF EXISTS] system_var_name]
     */
    public function parseResetPersist(TokenList $tokenList): ResetPersistCommand
    {
        $tokenList->expectKeywords(Keyword::RESET, Keyword::PERSIST);
        $ifExists = $tokenList->hasKeywords(Keyword::IF, Keyword::EXISTS);
        if ($ifExists) {
            $variable = $tokenList->expectName(null);
        } else {
            $variable = $tokenList->getName(null);
        }
        if ($variable !== null) {
            $variable = MysqlVariable::get($variable);
        }

        return new ResetPersistCommand($variable, $ifExists);
    }

    /**
     * @return non-empty-array<SetAssignment>
     */
    private function parseAssignments(TokenList $tokenList): array
    {
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
                    $name .= '.' . $tokenList->expectName(null);
                }
                $variable = $this->expressionParser->createSystemVariable($tokenList, $name, $scope);
            } elseif (($token = $tokenList->get(TokenType::AT_VARIABLE)) !== null) {
                // @foo, @@foo...
                $variable = $this->expressionParser->parseAtVariable($tokenList, $token->value);
            } else {
                $name = $tokenList->expectName(null);
                if ($tokenList->hasSymbol('.')) {
                    // NEW.foo etc.
                    $name2 = $tokenList->expectName(Entity::COLUMN);
                    $variable = new QualifiedName($name2, $name);
                } elseif (MysqlVariable::validateValue($name)) {
                    // system variable without scope
                    $variable = $this->expressionParser->createSystemVariable($tokenList, $name, Scope::get(Scope::SESSION));
                } elseif ($tokenList->inRoutine() !== null) {
                    // local variable
                    $variable = new SimpleName($name);
                } else {
                    // throws
                    $this->expressionParser->createSystemVariable($tokenList, $name, Scope::get(Scope::SESSION));
                    exit;
                }
            }

            $operator = $tokenList->expectAnyOperator(Operator::EQUAL, Operator::ASSIGN);

            if ($variable instanceof SystemVariable) {
                $type = MysqlVariable::getType($variable->getName());
                if (is_array($type)) {
                    $value = $tokenList->getNameOrStringEnumValue(...$type);
                    if ($value !== null) {
                        $expression = new EnumValueLiteral($value);
                    } else {
                        $expression = $this->expressionParser->parseAssignExpression($tokenList);
                    }
                } else {
                    $expression = $this->expressionParser->parseAssignExpression($tokenList);
                }
            } else {
                $expression = $this->expressionParser->parseAssignExpression($tokenList);
                if (($variable instanceof SimpleName || $variable instanceof QualifiedName) && $expression instanceof DefaultLiteral) {
                    throw new ParserException('Local variables cannot be set to DEFAULT.', $tokenList);
                }
            }

            $assignments[] = new SetAssignment($variable, $expression, $operator);
        } while ($tokenList->hasSymbol(','));

        return $assignments;
    }

}
