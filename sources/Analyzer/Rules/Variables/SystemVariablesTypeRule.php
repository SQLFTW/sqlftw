<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Analyzer\Rules\Variables;

use SqlFtw\Analyzer\AnalyzerContext;
use SqlFtw\Analyzer\AnalyzerRule;
use SqlFtw\Error\Error;
use SqlFtw\Sql\Dal\Set\SetVariablesCommand;
use SqlFtw\Sql\Expression\BaseType;
use SqlFtw\Sql\Expression\DefaultLiteral;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\KeywordLiteral;
use SqlFtw\Sql\Expression\OnOffLiteral;
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Expression\SystemVariable;
use SqlFtw\Sql\Expression\Value;
use SqlFtw\Sql\MysqlVariable;
use SqlFtw\Sql\SqlMode;
use SqlFtw\Sql\Statement;
use function count;
use function get_class;
use function gettype;
use function implode;
use function is_array;
use function is_int;
use function is_numeric;
use function is_object;
use function str_replace;

class SystemVariablesTypeRule implements AnalyzerRule
{

    public function getNodes(): array
    {
        return [SetVariablesCommand::class]; // todo SetNamesCommand, SetCharacterSetCommand
    }

    /**
     * @return list<Error>
     */
    public function process(Statement $statement, AnalyzerContext $context, int $flags): array
    {
        if ($statement instanceof SetVariablesCommand) {
            return $this->processSet($statement, $context);
        }

        return [];
    }

    /**
     * @return list<Error>
     */
    private function processSet(SetVariablesCommand $command, AnalyzerContext $context): array
    {
        $mode = $context->session->getMode();
        $strict = ($mode->fullValue & SqlMode::STRICT_ALL_TABLES) !== 0;

        $errors = [];
        foreach ($command->getAssignments() as $assignment) {
            $variable = $assignment->getVariable();
            if (!$variable instanceof SystemVariable) {
                continue;
            }

            $name = $variable->getName();
            $var = MysqlVariable::getInfo($name);
            $type = $var->type;
            if ($type === BaseType::UNSIGNED && !$strict && ($var->clamp || $var->clampMin)) {
                $type = BaseType::SIGNED;
            }
            $expression = $assignment->getExpression();
            $value = $context->resolver->resolve($expression);

            if (is_array($value)) {
                if (count($value) === 1) {
                    /** @var scalar|ExpressionNode|null $value */
                    $value = $value[0];
                } else {
                    $errors[] = Error::critical("variable.wrongType", "System variable {$name} can not be set to non-scalar value.", 0);
                    continue;
                }
            }
            if ($value instanceof DefaultLiteral) {
                if (!MysqlVariable::hasDefault($name)) {
                    $errors[] = Error::critical("variable.noDefault", "System variable {$name} can not be set to default value.", 0);
                }
                continue;
            }
            if ($value === null) {
                if (!$var->nullable) {
                    $errors[] = Error::critical("variable.invalidValue", "System variable {$name} is not nullable.", 0);
                }
                continue;
            }

            if ($value instanceof Placeholder) {
                continue;
            }

            if ($value instanceof SimpleName) {
                $value = $value->getName();
            } elseif ($value instanceof KeywordLiteral && !$value instanceof OnOffLiteral) {
                $value = $value->getValue();
            }

            if ($value instanceof ExpressionNode && !$value instanceof Value) {
                // not resolved
                // todo: insert real static type analysis here : ]
                $expressionString = str_replace("\n", "", $expression->serialize($context->formatter));
                $expressionType = get_class($expression);
                $message = "System variable {$name} assignment with expression \"{$expressionString}\" ({$expressionType}) was not checked.";
                $errors[] = Error::skipNotice("variable.notChecked", $message, 0);
            } else {
                if ($var->nonEmpty && $value === '') {
                    $errors[] = Error::critical("variable.invalidValue", "System variable {$name} can not be set to an empty value.", 0);
                }
                if ($var->nonZero && $value === 0) {
                    $errors[] = Error::critical("variable.invalidValue", "System variable {$name} can not be set to zero.", 0);
                }
                if (!$context->typeChecker->canBeCastedTo($value, $type, $var->values, $context->resolver->cast())) {
                    if ($var->values !== null) {
                        $type .= '(' . implode(',', $var->values) . ')';
                    }
                    $realType = is_object($value) ? get_class($value) : gettype($value);
                    $errors[] = Error::critical("variable.wongType", "System variable {$name} only accepts {$type}, but {$realType} given.", 0);
                    continue;
                }

                // validate bounds
                if (!is_numeric($value)) {
                    continue;
                }
                if ($var->min === null || $var->max === null) {
                    continue;
                } elseif ($value < $var->min && ($strict || (!$var->clamp && !$var->clampMin))) {
                    $errors[] = Error::critical("variable.invalidValue", "System variable {$name} value must be between {$var->min} and {$var->max}.", 0);
                } elseif ($value > $var->max && ($strict || !$var->clamp)) {
                    $errors[] = Error::critical("variable.invalidValue", "System variable {$name} value must be between {$var->min} and {$var->max}.", 0);
                }
                if ($var->increment === null) {
                    continue;
                } elseif (($strict || !$var->clamp) && (!is_int($value) || ($value % $var->increment) !== 0)) {
                    $errors[] = Error::critical("variable.invalidValue", "System variable {$name} value must be multiple of {$var->increment}.", 0);
                }
            }
        }

        return $errors;
    }

}
