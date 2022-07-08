<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dal\Set\SetCommand;
use SqlFtw\Sql\Expression\BinaryOperator;
use SqlFtw\Sql\Expression\BoolLiteral;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\DefaultLiteral;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\Parentheses;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Expression\StringValue;
use SqlFtw\Sql\Expression\SystemVariable;
use SqlFtw\Sql\Expression\UintLiteral;
use SqlFtw\Sql\Expression\UserVariable;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\MysqlVariable;
use SqlFtw\Sql\SqlMode;
use function trim;

class SettingsUpdater
{
    use StrictBehaviorMixin;

    public function updateSettings(Command $command, ParserSettings $settings, TokenList $tokenList): void
    {
        if ($command instanceof SetCommand) {
            foreach ($command->getAssignments() as $assignment) {
                $variable = $assignment->getVariable();
                if ($variable instanceof SystemVariable && $variable->getName() === MysqlVariable::SQL_MODE) {
                    $this->detectSqlModeChange($assignment->getExpression(), $settings, $tokenList);
                }
            }
        }

        // todo: SET NAMES
        // todo: SET CHARSET
        // SET innodb_strict_mode = ON
        // SET sql_require_primary_key = true
        // SET sql_safe_updates = ON
        // autocommit, character_set_client, character_set_results, character_set_connection
    }

    private function detectSqlModeChange(RootNode $expression, ParserSettings $settings, TokenList $tokenList): void
    {
        if ($expression instanceof SystemVariable && $expression->getName() === MysqlVariable::SQL_MODE) {
            // todo: tracking both session and global?
            $settings->setMode($settings->getPlatform()->getDefaultMode());
        } elseif ($expression instanceof StringValue) {
            $settings->setMode($this->sqlModeFromString(trim($expression->asString()), $settings->getPlatform(), $tokenList));
        } elseif ($expression instanceof BoolLiteral) {
            if ($expression->getValue() === Keyword::TRUE) {
                $settings->setMode($this->sqlModeFromInt(1, $settings->getPlatform(), $tokenList));
            } else {
                $settings->setMode($this->sqlModeFromInt(0, $settings->getPlatform(), $tokenList));
            }
        } elseif ($expression instanceof SimpleName) {
            $settings->setMode($this->sqlModeFromString($expression->getName(), $settings->getPlatform(), $tokenList));
        } elseif ($expression instanceof DefaultLiteral) {
            $settings->setMode($this->sqlModeFromString(Keyword::DEFAULT, $settings->getPlatform(), $tokenList));
        } elseif ($expression instanceof UintLiteral) {
            $settings->setMode($this->sqlModeFromInt($expression->asInteger(), $settings->getPlatform(), $tokenList));
        } elseif ($expression instanceof FunctionCall) {
            $function = $expression->getFunction();
            if ($function instanceof QualifiedName && $function->equals('sys.list_add')) {
                [$first, $second] = $expression->getArguments();
                if ($first instanceof SystemVariable && $first->getName() === MysqlVariable::SQL_MODE && $second instanceof StringValue) {
                    $expression = $settings->getMode()->getValue() . ',' . $second->asString();
                    // needed to expand groups
                    $mode = $this->sqlModeFromString($expression, $settings->getPlatform(), $tokenList);
                    $settings->setMode($mode);
                } else {
                    throw new ParserException('Cannot detect SQL_MODE change.', $tokenList);
                }
            } elseif ($function instanceof QualifiedName && $function->equals('sys.list_drop')) {
                [$first, $second] = $expression->getArguments();
                if ($first instanceof SystemVariable && $first->getName() === MysqlVariable::SQL_MODE && $second instanceof StringValue) {
                    $settings->setMode($settings->getMode()->remove($second->asString()));
                } else {
                    throw new ParserException('Cannot detect SQL_MODE change.', $tokenList);
                }
            } elseif ($function instanceof BuiltInFunction && $function->getValue() === BuiltInFunction::CAST) {
                // todo: skipped for now, needs evaluating expressions
                return;
            } elseif ($function instanceof BuiltInFunction && $function->getValue() === BuiltInFunction::CONCAT) {
                // todo: skipped for now, needs evaluating expressions
                return;
            } elseif ($function instanceof BuiltInFunction && $function->getValue() === BuiltInFunction::REGEXP_REPLACE) {
                // todo: skipped for now, needs evaluating expressions
                return;
            } else {
                throw new ParserException('Cannot detect SQL_MODE change.', $tokenList);
            }
        } elseif ($expression instanceof BinaryOperator) {
            // todo: skipped for now, needs evaluating expressions
            return;
        } elseif ($expression instanceof Parentheses) {
            // todo: skipped for now, needs evaluating expressions
            return;
        } elseif ($expression instanceof UserVariable) {
            // todo: no way to detect this
            return;
        } else {
            throw new ParserException('Cannot detect SQL_MODE change.', $tokenList);
        }
    }

    private function sqlModeFromString(string $mode, Platform $platform, TokenList $tokenList): SqlMode
    {
        try {
            return SqlMode::getFromString($mode, $platform);
        } catch (InvalidDefinitionException $e) {
            throw new ParserException($e->getMessage(), $tokenList, $e);
        }
    }

    private function sqlModeFromInt(int $mode, Platform $platform, TokenList $tokenList): SqlMode
    {
        try {
            return SqlMode::getFromInt($mode, $platform);
        } catch (InvalidDefinitionException $e) {
            throw new ParserException($e->getMessage(), $tokenList, $e);
        }
    }

}
