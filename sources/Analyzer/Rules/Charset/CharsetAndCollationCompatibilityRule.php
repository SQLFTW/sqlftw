<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Analyzer\Rules\Charset;

use SqlFtw\Analyzer\AnalyzerResult;
use SqlFtw\Analyzer\SimpleContext;
use SqlFtw\Analyzer\SimpleRule;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Ddl\Schema\AlterSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\CreateSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\SchemaCommand;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ConvertToCharsetAction;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use SqlFtw\Sql\Ddl\Table\Option\TableOption;
use SqlFtw\Sql\Expression\DefaultLiteral;
use SqlFtw\Sql\MysqlVariable;
use SqlFtw\Sql\Statement;
use SqlFtw\Sql\TableCommand;
use function count;

class CharsetAndCollationCompatibilityRule implements SimpleRule
{

    /**
     * @return AnalyzerResult[]
     */
    public function process(Statement $statement, SimpleContext $context, int $flags): array
    {
        if ($statement instanceof CreateSchemaCommand || $statement instanceof AlterSchemaCommand) {
            return $this->processSchema($statement, $context);
        } elseif ($statement instanceof CreateTableCommand || $statement instanceof AlterTableCommand) {
            return $this->processTable($statement, $context);
        }

        return [];
    }

    /**
     * @param CreateSchemaCommand|AlterSchemaCommand $command
     * @return AnalyzerResult[]
     */
    private function processSchema(SchemaCommand $command, SimpleContext $context): array
    {
        $session = $context->getSession();

        $results = [];
        $options =  $command->getOptions();
        $charset = $options->getCharset();
        if ($charset instanceof DefaultLiteral) {
            $charset = Charset::get($session->getSessionOrGlobalVariable(MysqlVariable::CHARACTER_SET_DATABASE));
        }
        $collation = $options->getCollation();
        if ($collation instanceof DefaultLiteral) {
            $collation = Collation::get($session->getSessionOrGlobalVariable(MysqlVariable::COLLATION_DATABASE));
        }
        if ($charset instanceof Charset && $collation instanceof Collation && !$charset->supportsCollation($collation)) {
            $results[] = new AnalyzerResult("Mismatch between database charset ({$charset->getValue()}) and collation ({$collation->getValue()}).", $this, $command);
        }

        return $results;
    }

    /**
     * @param CreateTableCommand|AlterTableCommand $command
     * @return AnalyzerResult[]
     */
    private function processTable(TableCommand $command, SimpleContext $context): array
    {
        $results = [];
        $options =  $command->getOptions();
        /** @var Charset $charset */
        $charset = $options->get(TableOption::CHARACTER_SET);
        if ($charset instanceof DefaultLiteral) {
            // todo: should take from schema
            $charset = null;
        }
        $collation = $options->get(TableOption::COLLATE);
        if ($collation instanceof DefaultLiteral) {
            // todo: should take from schema
            $collation = null;
        }
        if ($charset !== null && $collation !== null && !$charset->supportsCollation($collation)) {
            $results[] = new AnalyzerResult("Mismatch between table charset ({$charset->getValue()}) and collation ({$collation->getValue()}).", $this, $command);
        }

        if ($command instanceof AlterTableCommand) {
            /** @var ConvertToCharsetAction[] $convertActions */
            $convertActions = $command->getActions()->filter(ConvertToCharsetAction::class) ?? [];
            $convertAction = $convertActions[0] ?? null;
            if ($convertAction !== null) {
                $convertCharset = $convertAction->getCharset();
                if ($convertCharset instanceof DefaultLiteral) {
                    // todo: should take from schema
                    $convertCharset = null;
                }
                if ($convertCharset !== null) {
                    if ($charset !== null && !$convertCharset->equals($charset)) {
                        $results[] = new AnalyzerResult("Conflict between conversion charset ({$convertCharset->getValue()}) and table charset ({$charset->getValue()}).", $this, $command);
                    }
                    if ($collation !== null && !$convertCharset->supportsCollation($collation)) {
                        $results[] = new AnalyzerResult("Mismatch between conversion charset ({$convertCharset->getValue()}) and table collation ({$collation->getValue()}).", $this, $command);
                    }
                    $convertCollation = $convertAction->getCollation();
                    //!$convertAction->getCharset() instanceof DefaultLiteral
                    if ($convertCollation !== null && !$convertCharset->supportsCollation($convertCollation)) {
                        $results[] = new AnalyzerResult("Mismatch between conversion charset ({$convertCharset->getValue()}) and conversion collation ({$convertCollation->getValue()}).", $this, $command);
                    }
                    if (count($convertActions) > 1) {
                        foreach ($convertActions as $otherAction) {
                            $otherConvertCharset = $otherAction->getCharset();
                            if ($convertCharset instanceof DefaultLiteral) {
                                // todo: should take from schema
                                $convertCharset = null;
                            }
                            if ($otherConvertCharset !== null && !$convertCharset->equals($otherConvertCharset)) {
                                $results[] = new AnalyzerResult("Conflict between conversion charset ({$convertCharset->getValue()}) and other conversion charset ({$otherConvertCharset->getValue()}).", $this, $command);
                            }
                        }
                    }
                }
            }
        } else {
            foreach ($command->getColumns() as $column) {
                $type = $column->getType();
                $columnCharset = $type->getCharset();
                $columnCollation = $type->getCollation();
                if ($columnCharset !== null && $columnCollation !== null && !$columnCharset->supportsCollation($columnCollation)) {
                    $results[] = new AnalyzerResult("Mismatch between charset ({$columnCharset->getValue()}) and collation ({$columnCollation->getValue()}) on column {$column->getName()}.", $this, $command);
                }
            }
        }

        return $results;
    }

}
