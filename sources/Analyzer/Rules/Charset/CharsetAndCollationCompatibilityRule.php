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

    public const CHARSET_MISMATCH = "charset.charsetMismatch";
    public const CHARSET_COLLATION_MISMATCH = "charset.charsetCollationMismatch";

    public static function getIds(): array
    {
        return [
            self::CHARSET_MISMATCH,
            self::CHARSET_COLLATION_MISMATCH,
        ];
    }

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
        $options = $command->options;
        if ($options === null) {
            return [];
        }
        $charset = $options->charset;
        $collation = $options->collation;
        if ($charset !== null && $collation !== null && !$charset->supportsCollation($collation)) {
            $results[] = Error::critical(self::CHARSET_COLLATION_MISMATCH, "Mismatch between database charset ({$charset->value}) and collation ({$collation->value}).", 0);
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
        $options = $command->options;
        if ($options === null) {
            return [];
        }
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
            $errors[] = Error::critical(self::CHARSET_COLLATION_MISMATCH, "Mismatch between table charset ({$charset->value}) and collation ({$collation->value}).", 0);
        }

        if ($command instanceof AlterTableCommand) {
            /** @var list<ConvertToCharsetAction> $convertActions */
            $convertActions = $command->actions->filter(ConvertToCharsetAction::class);
            $convertAction = $convertActions[0] ?? null;
            if ($convertAction !== null) {
                $convertCharset = $convertAction->charset;
                if ($convertCharset instanceof DefaultLiteral) {
                    // todo: should take from schema
                    $convertCharset = null;
                }
                if ($convertCharset !== null) {
                    if ($charset !== null && $convertCharset->value !== $charset->value) {
                        $errors[] = Error::critical(self::CHARSET_MISMATCH, "Conflict between conversion charset ({$convertCharset->value}) and table charset ({$charset->value}).", 0);
                    }
                    if ($collation !== null && !$convertCharset->supportsCollation($collation)) {
                        $errors[] = Error::critical(self::CHARSET_COLLATION_MISMATCH, "Mismatch between conversion charset ({$convertCharset->value}) and table collation ({$collation->value}).", 0);
                    }
                    $convertCollation = $convertAction->collation;
                    //!$convertAction->getCharset() instanceof DefaultLiteral
                    if ($convertCollation !== null && !$convertCharset->supportsCollation($convertCollation)) {
                        $errors[] = Error::critical(self::CHARSET_COLLATION_MISMATCH, "Mismatch between conversion charset ({$convertCharset->value}) and conversion collation ({$convertCollation->value}).", 0);
                    }
                    if (count($convertActions) > 1) {
                        foreach ($convertActions as $otherAction) {
                            $otherConvertCharset = $otherAction->charset;
                            if ($otherConvertCharset instanceof DefaultLiteral) {
                                // todo: should take from schema
                                $otherConvertCharset = null;
                            }
                            if ($otherConvertCharset !== null && $convertCharset->value !== $otherConvertCharset->value) {
                                $errors[] = Error::critical(self::CHARSET_MISMATCH, "Conflict between conversion charset ({$convertCharset->value}) and other conversion charset ({$otherConvertCharset->value}).", 0);
                            }
                        }
                    }
                }
            }
        } else {
            foreach ($command->getColumns() as $column) {
                $type = $column->type;
                $columnCharset = $type->charset;
                $columnCollation = $type->collation;
                if ($columnCharset !== null && $columnCollation !== null && !$columnCharset->supportsCollation($columnCollation)) {
                    $errors[] = Error::critical(self::CHARSET_COLLATION_MISMATCH, "Mismatch between charset ({$columnCharset->value}) and collation ({$columnCollation->value}) on column {$column->name}.", 0);
                }
            }
        }

        return $errors;
    }

}
