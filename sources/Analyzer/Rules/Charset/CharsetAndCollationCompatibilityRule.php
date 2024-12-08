<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Analyzer\Rules\Charset;

use SqlFtw\Analyzer\AnalyzerContext;
use SqlFtw\Analyzer\AnalyzerRule;
use SqlFtw\Error\Error;
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
use SqlFtw\Sql\Statement;
use SqlFtw\Sql\TableCommand;
use function count;

class CharsetAndCollationCompatibilityRule implements AnalyzerRule
{

    public function getNodes(): array
    {
        return [CreateSchemaCommand::class, AlterSchemaCommand::class, CreateTableCommand::class, AlterTableCommand::class];
    }

    /**
     * @return list<Error>
     */
    public function process(Statement $statement, AnalyzerContext $context, int $flags): array
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
     * @return list<Error>
     */
    private function processSchema(SchemaCommand $command, AnalyzerContext $context): array
    {
        $results = [];
        $options = $command->getOptions();
        $charset = $options->getCharset();
        $collation = $options->getCollation();
        if ($charset !== null && $collation !== null && !$charset->supportsCollation($collation)) {
            $results[] = Error::critical("charset.charsetCollationMismatch", "Mismatch between database charset ({$charset->getValue()}) and collation ({$collation->getValue()}).", 0);
        }

        return $results;
    }

    /**
     * @param CreateTableCommand|AlterTableCommand $command
     * @return list<Error>
     */
    private function processTable(TableCommand $command, AnalyzerContext $context): array
    {
        $errors = [];
        $options = $command->getOptionsList();
        /** @var Charset|DefaultLiteral $charset */
        $charset = $options->get(TableOption::CHARACTER_SET);
        if ($charset instanceof DefaultLiteral) {
            // todo: should take from schema
            $charset = null;
        }
        /** @var Collation|DefaultLiteral $collation */
        $collation = $options->get(TableOption::COLLATE);
        if ($collation instanceof DefaultLiteral) {
            // todo: should take from schema
            $collation = null;
        }
        if ($charset !== null && $collation !== null && !$charset->supportsCollation($collation)) {
            $errors[] = Error::critical("charset.charsetCollationMismatch", "Mismatch between table charset ({$charset->getValue()}) and collation ({$collation->getValue()}).", 0);
        }

        if ($command instanceof AlterTableCommand) {
            /** @var list<ConvertToCharsetAction> $convertActions */
            $convertActions = $command->getActionsList()->filter(ConvertToCharsetAction::class);
            $convertAction = $convertActions[0] ?? null;
            if ($convertAction !== null) {
                $convertCharset = $convertAction->getCharset();
                if ($convertCharset instanceof DefaultLiteral) {
                    // todo: should take from schema
                    $convertCharset = null;
                }
                if ($convertCharset !== null) {
                    if ($charset !== null && !$convertCharset->equals($charset)) {
                        $errors[] = Error::critical("charset.charsetMismatch", "Conflict between conversion charset ({$convertCharset->getValue()}) and table charset ({$charset->getValue()}).", 0);
                    }
                    if ($collation !== null && !$convertCharset->supportsCollation($collation)) {
                        $errors[] = Error::critical("charset.charsetCollationMismatch", "Mismatch between conversion charset ({$convertCharset->getValue()}) and table collation ({$collation->getValue()}).", 0);
                    }
                    $convertCollation = $convertAction->getCollation();
                    //!$convertAction->getCharset() instanceof DefaultLiteral
                    if ($convertCollation !== null && !$convertCharset->supportsCollation($convertCollation)) {
                        $errors[] = Error::critical("charset.charsetCollationMismatch", "Mismatch between conversion charset ({$convertCharset->getValue()}) and conversion collation ({$convertCollation->getValue()}).", 0);
                    }
                    if (count($convertActions) > 1) {
                        foreach ($convertActions as $otherAction) {
                            $otherConvertCharset = $otherAction->getCharset();
                            if ($otherConvertCharset instanceof DefaultLiteral) {
                                // todo: should take from schema
                                $otherConvertCharset = null;
                            }
                            if ($otherConvertCharset !== null && !$convertCharset->equals($otherConvertCharset)) {
                                $errors[] = Error::critical("charset.charsetMismatch", "Conflict between conversion charset ({$convertCharset->getValue()}) and other conversion charset ({$otherConvertCharset->getValue()}).", 0);
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
                    $errors[] = Error::critical("charset.charsetCollationMismatch", "Mismatch between charset ({$columnCharset->getValue()}) and collation ({$columnCollation->getValue()}) on column {$column->getName()}.", 0);
                }
            }
        }

        return $errors;
    }

}
