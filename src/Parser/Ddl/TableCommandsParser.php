<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\Dml\SelectCommandParser;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddColumnsAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddConstraintAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddForeignKeyAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddIndexAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AddPartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AlterCheckAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AlterColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AlterConstraintAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AlterIndexAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\AnalyzePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ChangeColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\CheckPartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\CoalescePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ConvertToCharsetAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DisableKeysAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DiscardPartitionTablespaceAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DiscardTablespaceAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropCheckAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropConstraintAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropForeignKeyAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropIndexAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropPartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\DropPrimaryKeyAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\EnableKeysAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ExchangePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ImportPartitionTablespaceAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ImportTablespaceAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ModifyColumnAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\OptimizePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\OrderByAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RebuildPartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RemovePartitioningAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RenameIndexAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RenameToAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ReorganizePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RepairPartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\TruncatePartitionAction;
use SqlFtw\Sql\Ddl\Table\Alter\Action\UpgradePartitioningAction;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableActionType;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableAlgorithm;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableLock;
use SqlFtw\Sql\Ddl\Table\Alter\AlterTableOption;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\AnyCreateTableCommand;
use SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition;
use SqlFtw\Sql\Ddl\Table\Column\ColumnFormat;
use SqlFtw\Sql\Ddl\Table\Column\GeneratedColumnType;
use SqlFtw\Sql\Ddl\Table\Constraint\CheckDefinition;
use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition;
use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintType;
use SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyAction;
use SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition;
use SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyMatchType;
use SqlFtw\Sql\Ddl\Table\Constraint\ReferenceDefinition;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use SqlFtw\Sql\Ddl\Table\CreateTableLikeCommand;
use SqlFtw\Sql\Ddl\Table\DropTableCommand;
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;
use SqlFtw\Sql\Ddl\Table\Index\IndexType;
use SqlFtw\Sql\Ddl\Table\Option\StorageEngine;
use SqlFtw\Sql\Ddl\Table\Option\TableCompression;
use SqlFtw\Sql\Ddl\Table\Option\TableInsertMethod;
use SqlFtw\Sql\Ddl\Table\Option\TableOption;
use SqlFtw\Sql\Ddl\Table\Option\TableRowFormat;
use SqlFtw\Sql\Ddl\Table\Option\ThreeStateValue;
use SqlFtw\Sql\Ddl\Table\Partition\PartitionDefinition;
use SqlFtw\Sql\Ddl\Table\Partition\PartitioningCondition;
use SqlFtw\Sql\Ddl\Table\Partition\PartitioningConditionType;
use SqlFtw\Sql\Ddl\Table\Partition\PartitioningDefinition;
use SqlFtw\Sql\Ddl\Table\Partition\PartitionOption;
use SqlFtw\Sql\Ddl\Table\RenameTableCommand;
use SqlFtw\Sql\Ddl\Table\TableItem;
use SqlFtw\Sql\Ddl\Table\TruncateTableCommand;
use SqlFtw\Sql\Dml\DuplicateOption;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class TableCommandsParser
{
    use StrictBehaviorMixin;

    /** @var TypeParser */
    private $typeParser;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var IndexCommandsParser */
    private $indexCommandsParser;

    /** @var SelectCommandParser */
    private $selectCommandParser;

    public function __construct(
        TypeParser $typeParser,
        ExpressionParser $expressionParser,
        IndexCommandsParser $indexCommandsParser,
        SelectCommandParser $selectCommandParser
    )
    {
        $this->typeParser = $typeParser;
        $this->expressionParser = $expressionParser;
        $this->indexCommandsParser = $indexCommandsParser;
        $this->selectCommandParser = $selectCommandParser;
    }

    /**
     * ALTER TABLE tbl_name
     *     [alter_specification [, alter_specification] ...]
     *     [partition_options]
     *
     * alter_specification:
     *     table_options
     *   | ADD [COLUMN] col_name column_definition
     *         [FIRST | AFTER col_name ]
     *   | ADD [COLUMN] (col_name column_definition, ...)
     *   | ADD {INDEX|KEY} [index_name]
     *         [index_type] (index_col_name, ...) [index_option] ...
     *   | ADD [CONSTRAINT [symbol]] PRIMARY KEY
     *         [index_type] (index_col_name, ...) [index_option] ...
     *   | ADD [CONSTRAINT [symbol]]
     *         UNIQUE [INDEX|KEY] [index_name]
     *         [index_type] (index_col_name, ...) [index_option] ...
     *   | ADD FULLTEXT [INDEX|KEY] [index_name]
     *         (index_col_name, ...) [index_option] ...
     *   | ADD SPATIAL [INDEX|KEY] [index_name]
     *         (index_col_name, ...) [index_option] ...
     *   | ADD [CONSTRAINT [symbol]]
     *         FOREIGN KEY [index_name] (index_col_name, ...)
     *         reference_definition
     *   | ALGORITHM [=] {DEFAULT|INPLACE|COPY}
     *   | ALTER [COLUMN] col_name {SET DEFAULT literal | DROP DEFAULT}
     *   | CHANGE [COLUMN] old_col_name new_col_name column_definition
     *     [FIRST|AFTER col_name]
     *   | LOCK [=] {DEFAULT|NONE|SHARED|EXCLUSIVE}
     *   | MODIFY [COLUMN] col_name column_definition
     *         [FIRST | AFTER col_name]
     *   | DROP [COLUMN] col_name
     *   | DROP PRIMARY KEY
     *   | DROP {INDEX|KEY} index_name
     *   | DROP FOREIGN KEY fk_symbol
     *   | ALTER INDEX index_name {VISIBLE | INVISIBLE}
     *   | DISABLE KEYS
     *   | ENABLE KEYS
     *   | RENAME [TO|AS] new_tbl_name
     *   | RENAME {INDEX|KEY} old_index_name TO new_index_name
     *   | ORDER BY col_name [, col_name] ...
     *   | CONVERT TO CHARACTER SET charset_name [COLLATE collation_name]
     *   | [DEFAULT] CHARACTER SET [=] charset_name [COLLATE [=] collation_name]
     *   | DISCARD TABLESPACE
     *   | IMPORT TABLESPACE
     *   | FORCE
     *   | {WITHOUT|WITH} VALIDATION
     *   | ADD PARTITION (partition_definition)
     *   | DROP PARTITION partition_names
     *   | DISCARD PARTITION {partition_names | ALL} TABLESPACE
     *   | IMPORT PARTITION {partition_names | ALL} TABLESPACE
     *   | TRUNCATE PARTITION {partition_names | ALL}
     *   | COALESCE PARTITION number
     *   | REORGANIZE PARTITION partition_names INTO (partition_definitions)
     *   | EXCHANGE PARTITION partition_name WITH TABLE tbl_name [{WITH|WITHOUT} VALIDATION]
     *   | ANALYZE PARTITION {partition_names | ALL}
     *   | CHECK PARTITION {partition_names | ALL}
     *   | OPTIMIZE PARTITION {partition_names | ALL}
     *   | REBUILD PARTITION {partition_names | ALL}
     *   | REPAIR PARTITION {partition_names | ALL}
     *   | REMOVE PARTITIONING
     *   | UPGRADE PARTITIONING
     *
     * table_options:
     *     table_option [[,] table_option] ...  (see CREATE TABLE options)
     *
     * @param TokenList $tokenList
     * @return AlterTableCommand
     */
    public function parseAlterTable(TokenList $tokenList): AlterTableCommand
    {
        $tokenList->consumeKeywords(Keyword::ALTER, Keyword::TABLE);
        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

        $actions = [];
        $alterOptions = [];
        $tableOptions = [];
        do {
            $position = $tokenList->getPosition();
            $keyword = $tokenList->consume(TokenType::KEYWORD)->value;
            switch ($keyword) {
                case Keyword::ADD:
                    $second = $tokenList->mayConsume(TokenType::KEYWORD);
                    $second = $second !== null ? $second->value : null;
                    switch ($second) {
                        case null:
                        case Keyword::COLUMN:
                            if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
                                // ADD [COLUMN] (col_name column_definition, ...)
                                $addColumns = [];
                                do {
                                    $addColumns[] = $this->parseColumn($tokenList);
                                } while ($tokenList->mayConsumeComma());
                                $actions[] = new AddColumnsAction($addColumns);
                                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
                            } else {
                                // ADD [COLUMN] col_name column_definition [FIRST | AFTER col_name ]
                                $column = $this->parseColumn($tokenList);
                                $after = null;
                                if ($tokenList->mayConsumeKeyword(Keyword::FIRST)) {
                                    $after = ModifyColumnAction::FIRST;
                                } elseif ($tokenList->mayConsumeKeyword(Keyword::AFTER)) {
                                    $after = $tokenList->consumeName();
                                }
                                $actions[] = new AddColumnAction($column, $after);
                            }
                            break;
                        case Keyword::CONSTRAINT:
                            // ADD [CONSTRAINT [symbol]] FOREIGN KEY [index_name] (index_col_name, ...) reference_definition
                            // ADD [CONSTRAINT [symbol]] UNIQUE [INDEX|KEY] [index_name] [index_type] (index_col_name, ...) [index_option] ...
                            // ADD [CONSTRAINT [symbol]] PRIMARY KEY [index_type] (index_col_name, ...) [index_option] ...
                            $actions[] = new AddConstraintAction($this->parseConstraint($tokenList->resetPosition(-1)));
                            break;
                        case Keyword::FOREIGN:
                            // ADD [CONSTRAINT [symbol]] FOREIGN KEY [index_name] (index_col_name, ...) reference_definition
                            $actions[] = new AddForeignKeyAction($this->parseForeignKey($tokenList->resetPosition(-1)));
                            break;
                        case Keyword::PRIMARY:
                            // ADD [CONSTRAINT [symbol]] PRIMARY KEY [index_type] (index_col_name, ...) [index_option] ...
                            $index = $this->parseIndex($tokenList, true);
                            $actions[] = new AddIndexAction($index);
                            break;
                        case Keyword::FULLTEXT:
                        case Keyword::INDEX:
                        case Keyword::KEY:
                        case Keyword::SPATIAL:
                        case Keyword::UNIQUE:
                            // ADD FULLTEXT [INDEX|KEY] [index_name] (index_col_name, ...) [index_option] ...
                            // ADD {INDEX|KEY} [index_name] [index_type] (index_col_name, ...) [index_option] ...
                            // ADD SPATIAL [INDEX|KEY] [index_name] (index_col_name, ...) [index_option] ...
                            // ADD [CONSTRAINT [symbol]] UNIQUE [INDEX|KEY] [index_name] [index_type] (index_col_name, ...) [index_option] ...
                            $index = $this->parseIndex($tokenList->resetPosition(-1));
                            $actions[] = new AddIndexAction($index);
                            break;
                        case Keyword::PARTITION:
                            // ADD PARTITION (partition_definition)
                            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
                            $partition = $this->parsePartitionDefinition($tokenList);
                            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
                            $actions[] = new AddPartitionAction($partition);
                            break;
                        default:
                            $tokenList->expectedAnyKeyword(
                                Keyword::COLUMN,
                                Keyword::CONSTRAINT,
                                Keyword::FOREIGN,
                                Keyword::FULLTEXT,
                                Keyword::INDEX,
                                Keyword::KEY,
                                Keyword::PARTITION,
                                Keyword::PRIMARY,
                                Keyword::SPATIAL,
                                Keyword::UNIQUE
                            );
                    }
                    break;
                case Keyword::ALGORITHM:
                    // ALGORITHM [=] {DEFAULT|INPLACE|COPY}
                    $tokenList->mayConsumeOperator(Operator::EQUAL);
                    $alterOptions[Keyword::ALGORITHM] = $tokenList->consumeKeywordEnum(AlterTableAlgorithm::class);
                    break;
                case Keyword::ALTER:
                    if ($tokenList->mayConsumeKeyword(Keyword::INDEX)) {
                        // ALTER INDEX index_name {VISIBLE | INVISIBLE}
                        $index = $tokenList->consumeName();
                        $visible = $tokenList->consumeAnyKeyword(Keyword::VISIBLE, Keyword::INVISIBLE);
                        $actions[] = new AlterIndexAction($index, $visible === Keyword::VISIBLE);
                    } elseif ($tokenList->mayConsumeKeyword(Keyword::CONSTRAINT)) {
                        // ALTER CONSTRAINT symbol [NOT] ENFORCED
                        $constraint = $tokenList->consumeName();
                        $enforced = true;
                        if ($tokenList->mayConsumeKeyword(Keyword::NOT)) {
                            $enforced = false;
                        }
                        $tokenList->consumeKeyword(Keyword::ENFORCED);
                        $actions[] = new AlterConstraintAction($constraint, $enforced);
                    } elseif ($tokenList->mayConsumeKeyword(Keyword::CHECK)) {
                        // ALTER CHECK symbol [NOT] ENFORCED
                        $check = $tokenList->consumeName();
                        $enforced = true;
                        if ($tokenList->mayConsumeKeyword(Keyword::NOT)) {
                            $enforced = false;
                        }
                        $tokenList->consumeKeyword(Keyword::ENFORCED);
                        $actions[] = new AlterCheckAction($check, $enforced);
                    } else {
                        // ALTER [COLUMN] col_name {SET DEFAULT literal | DROP DEFAULT}
                        $tokenList->mayConsumeKeyword(Keyword::COLUMN);
                        $column = $tokenList->consumeName();
                        if ($tokenList->mayConsumeKeywords(Keyword::SET, Keyword::DEFAULT)) {
                            $value = $this->expressionParser->parseLiteralValue($tokenList);
                            $actions[] = new AlterColumnAction($column, $value);
                        } else {
                            $tokenList->consumeKeywords(Keyword::DROP, Keyword::DEFAULT);
                            $actions[] = new AlterColumnAction($column, null);
                        }
                    }
                    break;
                case Keyword::ANALYZE:
                    // ANALYZE PARTITION {partition_names | ALL}
                    $tokenList->consumeKeyword(Keyword::PARTITION);
                    $partitions = $this->parsePartitionNames($tokenList);
                    $actions[] = new AnalyzePartitionAction($partitions);
                    break;
                case Keyword::CHANGE:
                    // CHANGE [COLUMN] old_col_name new_col_name column_definition [FIRST|AFTER col_name]
                    $tokenList->mayConsumeKeyword(Keyword::COLUMN);
                    $oldName = $tokenList->consumeName();
                    $column = $this->parseColumn($tokenList);
                    $after = null;
                    if ($tokenList->mayConsumeKeyword(Keyword::FIRST)) {
                        $after = ModifyColumnAction::FIRST;
                    } elseif ($tokenList->mayConsumeKeyword(Keyword::AFTER)) {
                        $after = $tokenList->consumeName();
                    }
                    $actions[] = new ChangeColumnAction($oldName, $column, $after);
                    break;
                case Keyword::CHECK:
                    // CHECK PARTITION {partition_names | ALL}
                    $tokenList->consumeKeyword(Keyword::PARTITION);
                    $partitions = $this->parsePartitionNames($tokenList);
                    $actions[] = new CheckPartitionAction($partitions);
                    break;
                case Keyword::COALESCE:
                    // COALESCE PARTITION number
                    $tokenList->consumeKeyword(Keyword::PARTITION);
                    $actions[] = new CoalescePartitionAction($tokenList->consumeInt());
                    break;
                case Keyword::CONVERT:
                    // CONVERT TO CHARACTER SET charset_name [COLLATE collation_name]
                    $tokenList->consumeKeywords(Keyword::TO, Keyword::CHARACTER, Keyword::SET);
                    /** @var Charset $charset */
                    $charset = $tokenList->consumeNameOrStringEnum(Charset::class);
                    $collation = null;
                    if ($tokenList->mayConsumeKeyword(Keyword::COLLATE)) {
                        $collation = Collation::get($tokenList->consumeNameOrString());
                    }
                    $actions[] = new ConvertToCharsetAction($charset, $collation);
                    break;
                case Keyword::DISCARD:
                    $second = $tokenList->consumeAnyKeyword(Keyword::TABLESPACE, Keyword::PARTITION);
                    if ($second === Keyword::TABLESPACE) {
                        // DISCARD TABLESPACE
                        $actions[] = new DiscardTablespaceAction();
                    } else {
                        // DISCARD PARTITION {partition_names | ALL} TABLESPACE
                        $partitions = $this->parsePartitionNames($tokenList);
                        $tokenList->consumeKeyword(Keyword::TABLESPACE);
                        $actions[] = new DiscardPartitionTablespaceAction($partitions);
                    }
                    break;
                case Keyword::DISABLE:
                    // DISABLE KEYS
                    $tokenList->consumeKeyword(Keyword::KEYS);
                    $actions[] = new DisableKeysAction();
                    break;
                case Keyword::DROP:
                    $second = $tokenList->mayConsume(TokenType::KEYWORD);
                    $second = $second !== null ? $second->value : null;
                    switch ($second) {
                        case null:
                        case Keyword::COLUMN:
                            // DROP [COLUMN] col_name
                            $tokenList->mayConsumeKeyword(Keyword::COLUMN);
                            $actions[] = new DropColumnAction($tokenList->consumeName());
                            break;
                        case Keyword::INDEX:
                        case Keyword::KEY:
                            // DROP {INDEX|KEY} index_name
                            $actions[] = new DropIndexAction($tokenList->consumeName());
                            break;
                        case Keyword::FOREIGN:
                            // DROP FOREIGN KEY fk_symbol
                            $tokenList->consumeKeyword(Keyword::KEY);
                            $actions[] = new DropForeignKeyAction($tokenList->consumeName());
                            break;
                        case Keyword::CONSTRAINT:
                            // DROP CONSTRAINT symbol
                            $actions[] = new DropConstraintAction($tokenList->consumeName());
                            break;
                        case Keyword::CHECK:
                            // DROP CHECK symbol
                            $actions[] = new DropCheckAction($tokenList->consumeName());
                            break;
                        case Keyword::PARTITION:
                            // DROP PARTITION partition_names
                            $partitions = $this->parsePartitionNames($tokenList);
                            $actions[] = new DropPartitionAction($partitions);
                            break;
                        case Keyword::PRIMARY:
                            // DROP PRIMARY KEY
                            $tokenList->consumeKeyword(Keyword::KEY);
                            $actions[] = new DropPrimaryKeyAction();
                            break;
                        default:
                            $tokenList->expectedAnyKeyword(Keyword::COLUMN, Keyword::INDEX, Keyword::KEY, Keyword::FOREIGN, Keyword::PARTITION, Keyword::PRIMARY);
                    }
                    break;
                case Keyword::ENABLE:
                    // ENABLE KEYS
                    $tokenList->consumeKeyword(Keyword::KEYS);
                    $actions[] = new EnableKeysAction();
                    break;
                case Keyword::EXCHANGE:
                    // EXCHANGE PARTITION partition_name WITH TABLE tbl_name [{WITH|WITHOUT} VALIDATION]
                    $tokenList->consumeKeyword(Keyword::PARTITION);
                    $partition = $tokenList->consumeName();
                    $tokenList->consumeKeywords(Keyword::WITH, Keyword::TABLE);
                    $table = new QualifiedName(...$tokenList->consumeQualifiedName());
                    $validation = $tokenList->mayConsumeAnyKeyword(Keyword::WITH, Keyword::WITHOUT);
                    if ($validation === Keyword::WITH) {
                        $tokenList->consumeKeyword(Keyword::VALIDATION);
                        $validation = true;
                    } elseif ($validation === Keyword::WITHOUT) {
                        $tokenList->consumeKeyword(Keyword::VALIDATION);
                        $validation = false;
                    }
                    $actions[] = new ExchangePartitionAction($partition, $table, $validation);
                    break;
                case Keyword::FORCE:
                    // FORCE
                    $alterOptions[AlterTableOption::FORCE] = true;
                    break;
                case Keyword::IMPORT:
                    $second = $tokenList->consumeAnyKeyword(Keyword::TABLESPACE, Keyword::PARTITION);
                    if ($second === Keyword::TABLESPACE) {
                        // IMPORT TABLESPACE
                        $actions[] = new ImportTablespaceAction();
                    } else {
                        // IMPORT PARTITION {partition_names | ALL} TABLESPACE
                        $partitions = $this->parsePartitionNames($tokenList);
                        $tokenList->consumeKeyword(Keyword::TABLESPACE);
                        $actions[] = new ImportPartitionTablespaceAction($partitions);
                    }
                    break;
                case Keyword::LOCK:
                    // LOCK [=] {DEFAULT|NONE|SHARED|EXCLUSIVE}
                    $tokenList->mayConsumeOperator(Operator::EQUAL);
                    $alterOptions[Keyword::LOCK] = $tokenList->consumeKeywordEnum(AlterTableLock::class);
                    break;
                case Keyword::MODIFY:
                    // MODIFY [COLUMN] col_name column_definition [FIRST | AFTER col_name]
                    $tokenList->mayConsumeKeyword(Keyword::COLUMN);
                    $column = $this->parseColumn($tokenList);
                    $after = null;
                    if ($tokenList->mayConsumeKeyword(Keyword::FIRST)) {
                        $after = ModifyColumnAction::FIRST;
                    } elseif ($tokenList->mayConsumeKeyword(Keyword::AFTER)) {
                        $after = $tokenList->consumeName();
                    }
                    $actions[] = new ModifyColumnAction($column, $after);
                    break;
                case Keyword::OPTIMIZE:
                    // OPTIMIZE PARTITION {partition_names | ALL}
                    $tokenList->consumeKeyword(Keyword::PARTITION);
                    $partitions = $this->parsePartitionNames($tokenList);
                    $actions[] = new OptimizePartitionAction($partitions);
                    break;
                case Keyword::ORDER:
                    // ORDER BY col_name [, col_name] ...
                    $tokenList->consumeKeyword(Keyword::BY);
                    $columns = [];
                    do {
                        $columns[] = $tokenList->consumeName();
                    } while ($tokenList->mayConsumeComma());
                    $actions[] = new OrderByAction($columns);
                    break;
                case Keyword::REBUILD:
                    // REBUILD PARTITION {partition_names | ALL}
                    $tokenList->consumeKeyword(Keyword::PARTITION);
                    $partitions = $this->parsePartitionNames($tokenList);
                    $actions[] = new RebuildPartitionAction($partitions);
                    break;
                case Keyword::REMOVE:
                    // REMOVE PARTITIONING
                    $tokenList->consumeKeyword(Keyword::PARTITIONING);
                    $actions[] = new RemovePartitioningAction();
                    break;
                case Keyword::RENAME:
                    if ($tokenList->mayConsumeAnyKeyword(Keyword::INDEX, Keyword::KEY)) {
                        // RENAME {INDEX|KEY} old_index_name TO new_index_name
                        $oldName = $tokenList->consumeName();
                        $tokenList->consumeKeyword(Keyword::TO);
                        $newName = $tokenList->consumeName();
                        $actions[] = new RenameIndexAction($oldName, $newName);
                    } else {
                        // RENAME [TO|AS] new_tbl_name
                        $tokenList->mayConsumeAnyKeyword(Keyword::TO, Keyword::AS);
                        $newName = new QualifiedName(...$tokenList->consumeQualifiedName());
                        $actions[] = new RenameToAction($newName);
                    }
                    break;
                case Keyword::REORGANIZE:
                    // REORGANIZE PARTITION partition_names INTO (partition_definitions, ...)
                    $tokenList->consumeKeyword(Keyword::PARTITION);
                    $oldPartitions = $this->parsePartitionNames($tokenList);
                    $tokenList->consumeKeyword(Keyword::INTO);
                    $tokenList->consume(TokenType::LEFT_PARENTHESIS);
                    $newPartitions = [];
                    do {
                        $newPartitions[] = $this->parsePartitionDefinition($tokenList);
                    } while ($tokenList->mayConsumeComma());
                    $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
                    $actions[] = new ReorganizePartitionAction($oldPartitions, $newPartitions);
                    break;
                case Keyword::REPAIR:
                    // REPAIR PARTITION {partition_names | ALL}
                    $tokenList->consumeKeyword(Keyword::PARTITION);
                    $partitions = $this->parsePartitionNames($tokenList);
                    $actions[] = new RepairPartitionAction($partitions);
                    break;
                case Keyword::TRUNCATE:
                    // TRUNCATE PARTITION {partition_names | ALL}
                    $tokenList->consumeKeyword(Keyword::PARTITION);
                    $partitions = $this->parsePartitionNames($tokenList);
                    $actions[] = new TruncatePartitionAction($partitions);
                    break;
                case Keyword::UPGRADE:
                    // UPGRADE PARTITIONING
                    $tokenList->consumeKeyword(Keyword::PARTITIONING);
                    $actions[] = new UpgradePartitioningAction();
                    break;
                case Keyword::WITH:
                    // {WITHOUT|WITH} VALIDATION
                    $tokenList->consumeKeyword(Keyword::VALIDATION);
                    $alterOptions[Keyword::VALIDATION] = true;
                    break;
                case Keyword::WITHOUT:
                    // {WITHOUT|WITH} VALIDATION
                    $tokenList->consumeKeyword(Keyword::VALIDATION);
                    $alterOptions[Keyword::VALIDATION] = false;
                    break;
                default:
                    [$option, $value] = $this->parseTableOption($tokenList->resetPosition($position));
                    if ($option === null) {
                        $keywords = AlterTableActionType::getAllowedValues() + AlterTableOption::getAllowedValues()
                            + [Keyword::ALGORITHM, Keyword::LOCK, Keyword::WITH, Keyword::WITHOUT];
                        $tokenList->expectedAnyKeyword(...$keywords);
                    }
                    $tableOptions[$option] = $value;
            }
        } while ($tokenList->mayConsumeComma());

        $tokenList->expectEnd();

        return new AlterTableCommand($name, $actions, $alterOptions, $tableOptions);
    }

    /**
     * CREATE [TEMPORARY] TABLE [IF NOT EXISTS] tbl_name
     *     (create_definition, ...)
     *     [table_options]
     *     [partition_options]
     *
     * CREATE [TEMPORARY] TABLE [IF NOT EXISTS] tbl_name
     *     [(create_definition, ...)]
     *     [table_options]
     *     [partition_options]
     *     [IGNORE | REPLACE]
     *     [AS] query_expression
     *
     * CREATE [TEMPORARY] TABLE [IF NOT EXISTS] tbl_name
     *     { LIKE old_tbl_name | (LIKE old_tbl_name) }
     *
     * query_expression:
     *     SELECT ...   (Some valid select or union statement)
     *
     * @param TokenList $tokenList
     * @return AnyCreateTableCommand
     */
    public function parseCreateTable(TokenList $tokenList): AnyCreateTableCommand
    {
        $tokenList->consumeKeyword(Keyword::CREATE);
        $temporary = (bool) $tokenList->mayConsumeKeyword(Keyword::TEMPORARY);
        $tokenList->consumeKeyword(Keyword::TABLE);
        $ifNotExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);
        $table = new QualifiedName(...$tokenList->consumeQualifiedName());

        $position = $tokenList->getPosition();
        $bodyOpen = $tokenList->mayConsume(TokenType::LEFT_PARENTHESIS);
        if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
            $oldTable = new QualifiedName(...$tokenList->consumeQualifiedName());
            if ($bodyOpen !== null) {
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            }

            return new CreateTableLikeCommand($table, $oldTable, $temporary, $ifNotExists);
        }

        $items = null;
        if ($bodyOpen !== null) {
            $items = $this->parseCreateTableBody($tokenList->resetPosition($position));
        }

        $options = [];
        if (!$tokenList->isFinished()) {
            do {
                [$option, $value] = $this->parseTableOption($tokenList);
                if ($option === null) {
                    $keywords = AlterTableOption::getAllowedValues();
                    $tokenList->expectedAnyKeyword(...$keywords);
                }
                $options[$option] = $value;
            } while ($tokenList->mayConsumeComma());
        }

        $partitioning = null;
        if ($tokenList->mayConsumeAnyKeyword(Keyword::PARTITION)) {
            $partitioning = $this->parsePartitioning($tokenList->resetPosition(-1));
        }

        /** @var DuplicateOption|null $duplicateOption */
        $duplicateOption = $tokenList->mayConsumeKeywordEnum(DuplicateOption::class);
        $select = null;
        if ($tokenList->mayConsumeKeyword(Keyword::AS) || $items === null || $duplicateOption !== null || !$tokenList->isFinished()) {
            $select = $this->selectCommandParser->parseSelect($tokenList);
        }
        $tokenList->expectEnd();

        return new CreateTableCommand($table, $items, $options, $partitioning, $temporary, $ifNotExists, $duplicateOption, $select);
    }

    /**
     * (create_definition, ...)
     *
     * create_definition:
     *     col_name column_definition
     *   | [CONSTRAINT [symbol]] PRIMARY KEY [index_type] (index_col_name, ...) [index_option] ...
     *   | [CONSTRAINT [symbol]] UNIQUE [INDEX|KEY] [index_name] [index_type] (index_col_name, ...) [index_option] ...
     *   | {INDEX|KEY} [index_name] [index_type] (index_col_name, ...) [index_option] ...
     *   | {FULLTEXT|SPATIAL} [INDEX|KEY] [index_name] (index_col_name, ...) [index_option] ...
     *   | [CONSTRAINT [symbol]] FOREIGN KEY [index_name] (index_col_name, ...) reference_definition
     *   | check_constraint_definition
     *
     * @param TokenList $tokenList
     * @return TableItem[]
     */
    private function parseCreateTableBody(TokenList $tokenList): array
    {
        $items = [];
        $tokenList->consume(TokenType::LEFT_PARENTHESIS);

        do {
            if ($tokenList->mayConsumeKeyword(Keyword::CHECK)) {
                $items[] = $this->parseCheck($tokenList);
            } elseif ($tokenList->mayConsumeAnyKeyword(Keyword::INDEX, Keyword::KEY, Keyword::FULLTEXT, Keyword::SPATIAL, Keyword::UNIQUE)) {
                $items[] = $this->parseIndex($tokenList->resetPosition(-1));
            } elseif ($tokenList->mayConsumeKeyword(Keyword::PRIMARY)) {
                $items[] = $this->parseIndex($tokenList, true);
            } elseif ($tokenList->mayConsumeKeyword(Keyword::FOREIGN)) {
                $items[] = $this->parseForeignKey($tokenList->resetPosition(-1));
            } elseif ($tokenList->mayConsumeKeyword(Keyword::CONSTRAINT)) {
                $items[] = $this->parseConstraint($tokenList->resetPosition(-1));
            } else {
                $items[] = $this->parseColumn($tokenList);
            }
        } while ($tokenList->mayConsumeComma());

        $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

        return $items;
    }

    /**
     * create_definition:
     *     col_name column_definition
     *
     * column_definition:
     *     data_type [NOT NULL | NULL] [DEFAULT default_value]
     *       [AUTO_INCREMENT] [UNIQUE [KEY] | [PRIMARY] KEY]
     *       [COMMENT 'string']
     *       [COLUMN_FORMAT {FIXED|DYNAMIC|DEFAULT}]
     *       [reference_definition]
     *       [check_constraint_definition]
     *   | data_type [GENERATED ALWAYS] AS (expression)
     *       [VIRTUAL | STORED] [UNIQUE [KEY]] [COMMENT comment]
     *       [NOT NULL | NULL] [[PRIMARY] KEY]
     *
     * @param TokenList $tokenList
     * @return ColumnDefinition
     */
    private function parseColumn(TokenList $tokenList): ColumnDefinition
    {
        $name = $tokenList->consumeName();
        $type = $this->typeParser->parseType($tokenList);

        $keyword = $tokenList->mayConsumeAnyKeyword(Keyword::GENERATED, Keyword::AS);
        if ($keyword === null) {
            // [NOT NULL | NULL]
            $null = null;
            if ($tokenList->mayConsumeKeywords(Keyword::NOT, Keyword::NULL)) {
                $null = false;
            } elseif ($tokenList->mayConsumeKeyword(Keyword::NULL)) {
                $null = true;
            }

            // [DEFAULT default_value]
            $default = null;
            if ($tokenList->mayConsumeKeyword(Keyword::DEFAULT)) {
                $default = $this->expressionParser->parseLiteralValue($tokenList);
            }

            // [AUTO_INCREMENT]
            $autoIncrement = false;
            if ($tokenList->mayConsumeKeyword(Keyword::AUTO_INCREMENT)) {
                $autoIncrement = true;
            }

            // [UNIQUE [KEY] | [PRIMARY] KEY]
            $index = null;
            if ($tokenList->mayConsumeKeyword(Keyword::UNIQUE)) {
                $tokenList->mayConsumeKeyword(Keyword::KEY);
                $index = IndexType::get(IndexType::UNIQUE);
            } elseif ($tokenList->mayConsumeKeyword(Keyword::PRIMARY)) {
                $tokenList->mayConsumeKeyword(Keyword::KEY);
                $index = IndexType::get(IndexType::PRIMARY);
            } elseif ($tokenList->mayConsumeKeyword(Keyword::KEY)) {
                $index = IndexType::get(IndexType::INDEX);
            }

            // [COMMENT 'string']
            $comment = null;
            if ($tokenList->mayConsumeKeyword(Keyword::COMMENT)) {
                $comment = $tokenList->consumeString();
            }

            // [COLUMN_FORMAT {FIXED|DYNAMIC|DEFAULT}]
            $columnFormat = null;
            if ($tokenList->mayConsumeKeyword(Keyword::COLUMN_FORMAT)) {
                /** @var ColumnFormat $columnFormat */
                $columnFormat = $tokenList->consumeKeywordEnum(ColumnFormat::class);
            }

            // [reference_definition]
            $reference = null;
            if ($tokenList->mayConsumeKeyword(Keyword::REFERENCES)) {
                $reference = $this->parseReference($tokenList->resetPosition(-1));
            }

            // [check_constraint_definition]
            $check = null;
            if ($tokenList->mayConsumeKeyword(Keyword::CHECK)) {
                $check = $this->parseCheck($tokenList);
            }

            return new ColumnDefinition($name, $type, $default, $null, $autoIncrement, $comment, $index, $columnFormat, $reference, $check);
        } else {
            if ($keyword === Keyword::GENERATED) {
                $tokenList->consumeKeywords(Keyword::ALWAYS, Keyword::AS);
            }
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $expression = $this->expressionParser->parseExpression($tokenList);
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

            /** @var GeneratedColumnType $generatedType */
            $generatedType = $tokenList->mayConsumeKeywordEnum(GeneratedColumnType::class);
            $index = null;
            if ($tokenList->mayConsumeKeyword(Keyword::UNIQUE)) {
                $tokenList->mayConsumeKeyword(Keyword::KEY);
                $index = IndexType::get(IndexType::UNIQUE);
            }
            $comment = null;
            if ($tokenList->mayConsumeKeyword(Keyword::COMMENT)) {
                $comment = $tokenList->consumeString();
            }
            $null = null;
            if ($tokenList->mayConsumeKeywords(Keyword::NOT, Keyword::NULL)) {
                $null = false;
            } elseif ($tokenList->mayConsumeKeyword(Keyword::NULL)) {
                $null = true;
            }

            if ($tokenList->mayConsumeKeyword(Keyword::PRIMARY)) {
                $tokenList->mayConsumeKeyword(Keyword::KEY);
                $index = IndexType::get(IndexType::PRIMARY);
            } elseif ($tokenList->mayConsumeKeyword(Keyword::KEY)) {
                $index = IndexType::get(IndexType::INDEX);
            }

            return ColumnDefinition::createGenerated($name, $type, $expression, $generatedType, $null, $comment, $index);
        }
    }

    /**
     * check_constraint_definition:
     *     [CONSTRAINT [symbol]] CHECK (expr) [[NOT] ENFORCED]
     *
     * @param TokenList $tokenList
     * @return CheckDefinition
     */
    private function parseCheck(TokenList $tokenList): CheckDefinition
    {
        $tokenList->consume(TokenType::LEFT_PARENTHESIS);
        $expression = $this->expressionParser->parseExpression($tokenList);
        $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

        $enforced = null;
        if ($tokenList->mayConsumeKeyword(Keyword::ENFORCED)) {
            $enforced = true;
        } elseif ($tokenList->mayConsumeKeywords(Keyword::NOT, Keyword::ENFORCED)) {
            $enforced = false;
        }

        return new CheckDefinition($expression, $enforced);
    }

    /**
     * create_definition:
     *   | [CONSTRAINT [symbol]] PRIMARY KEY [index_type] (index_col_name, ...) [index_option] ...
     *   | [CONSTRAINT [symbol]] UNIQUE [INDEX|KEY] [index_name] [index_type] (index_col_name, ...) [index_option] ...
     *   | {INDEX|KEY} [index_name] [index_type] (index_col_name, ...) [index_option] ...
     *   | {FULLTEXT|SPATIAL} [INDEX|KEY] [index_name] (index_col_name, ...) [index_option] ...
     *
     * @param TokenList $tokenList
     * @param bool $primary
     * @return IndexDefinition
     */
    private function parseIndex(TokenList $tokenList, bool $primary = false): IndexDefinition
    {
        if ($primary) {
            $index = $this->indexCommandsParser->parseIndexDefinition($tokenList, true);

            return $index->duplicateAsPrimary();
        } else {
            return $this->indexCommandsParser->parseIndexDefinition($tokenList, true);
        }
    }

    /**
     * create_definition:
     *   | [CONSTRAINT [symbol]] PRIMARY KEY [index_type] (index_col_name, ...) [index_option] ...
     *   | [CONSTRAINT [symbol]] UNIQUE [INDEX|KEY] [index_name] [index_type] (index_col_name, ...) [index_option] ...
     *   | [CONSTRAINT [symbol]] FOREIGN KEY [index_name] (index_col_name, ...) reference_definition
     *   | [CONSTRAINT [symbol]] CHECK (expr) [[NOT] ENFORCED]
     *
     * @param TokenList $tokenList
     * @return ConstraintDefinition
     */
    private function parseConstraint(TokenList $tokenList): ConstraintDefinition
    {
        $tokenList->mayConsumeKeyword(Keyword::CONSTRAINT);
        $name = $tokenList->mayConsumeName();

        $keyword = $tokenList->consumeAnyKeyword(Keyword::PRIMARY, Keyword::UNIQUE, Keyword::FOREIGN, Keyword::CHECK);
        if ($keyword === Keyword::PRIMARY) {
            $type = ConstraintType::get(ConstraintType::PRIMARY_KEY);
            $body = $this->parseIndex($tokenList, true);

            return new ConstraintDefinition($type, $name, $body);
        } elseif ($keyword === Keyword::UNIQUE) {
            $type = ConstraintType::get(ConstraintType::UNIQUE_KEY);
            $body = $this->parseIndex($tokenList->resetPosition(-1));

            return new ConstraintDefinition($type, $name, $body);
        } elseif ($keyword === Keyword::FOREIGN) {
            $type = ConstraintType::get(ConstraintType::FOREIGN_KEY);
            $body = $this->parseForeignKey($tokenList->resetPosition(-1));

            return new ConstraintDefinition($type, $name, $body);
        } else {
            $type = ConstraintType::get(ConstraintType::CHECK);
            $body = $this->parseCheck($tokenList);

            return new ConstraintDefinition($type, $name, $body);
        }
    }

    /**
     * create_definition:
     *     [CONSTRAINT [symbol]] FOREIGN KEY
     *         [index_name] (index_col_name, ...) reference_definition
     *
     * @param TokenList $tokenList
     * @return ForeignKeyDefinition
     */
    private function parseForeignKey(TokenList $tokenList): ForeignKeyDefinition
    {
        $tokenList->consumeKeywords(Keyword::FOREIGN, Keyword::KEY);
        $indexName = $tokenList->mayConsumeName();

        $columns = $this->parseColumnList($tokenList);
        $reference = $this->parseReference($tokenList);

        return new ForeignKeyDefinition($columns, $reference, $indexName);
    }

    /**
     * reference_definition:
     *     REFERENCES tbl_name (index_col_name, ...)
     *     [MATCH FULL | MATCH PARTIAL | MATCH SIMPLE]
     *     [ON DELETE reference_option]
     *     [ON UPDATE reference_option]
     *
     * reference_option:
     *     RESTRICT | CASCADE | SET NULL | NO ACTION | SET DEFAULT
     *
     * @param TokenList $tokenList
     * @return ReferenceDefinition
     */
    private function parseReference(TokenList $tokenList): ReferenceDefinition
    {
        $tokenList->consumeKeyword(Keyword::REFERENCES);
        $table = new QualifiedName(...$tokenList->consumeQualifiedName());

        $columns = $this->parseColumnList($tokenList);

        $matchType = null;
        if ($tokenList->mayConsumeKeyword(Keyword::MATCH)) {
            /** @var ForeignKeyMatchType $matchType */
            $matchType = $tokenList->consumeKeywordEnum(ForeignKeyMatchType::class);
        }

        $onDelete = $onUpdate = null;
        if ($tokenList->mayConsumeKeywords(Keyword::ON, Keyword::DELETE)) {
            $onDelete = $this->parseForeignKeyAction($tokenList);
        }
        if ($tokenList->mayConsumeKeywords(Keyword::ON, Keyword::UPDATE)) {
            $onUpdate = $this->parseForeignKeyAction($tokenList);
        }

        return new ReferenceDefinition($table, $columns, $onDelete, $onUpdate, $matchType);
    }

    private function parseForeignKeyAction(TokenList $tokenList): ForeignKeyAction
    {
        $keyword = $tokenList->consumeAnyKeyword(Keyword::RESTRICT, Keyword::CASCADE, Keyword::NO, Keyword::SET);
        if ($keyword === Keyword::NO) {
            $tokenList->consumeKeyword(Keyword::ACTION);

            return ForeignKeyAction::get(ForeignKeyAction::NO_ACTION);
        } elseif ($keyword === Keyword::SET) {
            $keyword = $tokenList->consumeAnyKeyword(Keyword::NULL, Keyword::DEFAULT);

            return ForeignKeyAction::get(Keyword::SET . ' ' . $keyword);
        } else {
            return ForeignKeyAction::get($keyword);
        }
    }

    /**
     * table_option:
     *     AUTO_INCREMENT [=] value
     *   | AVG_ROW_LENGTH [=] value
     *   | [DEFAULT] CHARACTER SET [=] charset_name
     *   | CHECKSUM [=] {0 | 1}
     *   | [DEFAULT] COLLATE [=] collation_name
     *   | COMMENT [=] 'string'
     *   | COMPRESSION [=] {'ZLIB'|'LZ4'|'NONE'}
     *   | CONNECTION [=] 'connect_string'
     *   | DATA DIRECTORY [=] 'absolute path to directory'
     *   | DELAY_KEY_WRITE [=] {0 | 1}
     *   | ENCRYPTION [=] {'Y' | 'N'}
     *   | ENGINE [=] engine_name
     *   | INDEX DIRECTORY [=] 'absolute path to directory'
     *   | INSERT_METHOD [=] { NO | FIRST | LAST }
     *   | KEY_BLOCK_SIZE [=] value
     *   | MAX_ROWS [=] value
     *   | MIN_ROWS [=] value
     *   | PACK_KEYS [=] {0 | 1 | DEFAULT}
     *   | PASSWORD [=] 'string'
     *   | ROW_FORMAT [=] {DEFAULT|DYNAMIC|FIXED|COMPRESSED|REDUNDANT|COMPACT}
     *   | STATS_AUTO_RECALC [=] {DEFAULT|0|1}
     *   | STATS_PERSISTENT [=] {DEFAULT|0|1}
     *   | STATS_SAMPLE_PAGES [=] value
     *   | TABLESPACE tablespace_name
     *   | UNION [=] (tbl_name[,tbl_name]...)
     *
     * @param TokenList $tokenList
     * @return mixed[] (string $name, mixed $value)
     */
    private function parseTableOption(TokenList $tokenList): array
    {
        $keyword = $tokenList->consume(TokenType::KEYWORD)->value;
        switch ($keyword) {
            case Keyword::AUTO_INCREMENT:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::AUTO_INCREMENT, $tokenList->consumeInt()];
            case Keyword::AVG_ROW_LENGTH:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::AVG_ROW_LENGTH, $tokenList->consumeInt()];
            case Keyword::CHARACTER:
                $tokenList->consumeKeyword(Keyword::SET);
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::CHARACTER_SET, Charset::get($tokenList->consumeString())];
            case Keyword::CHECKSUM:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::CHECKSUM, $tokenList->consumeBool()];
            case Keyword::COLLATE:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::COLLATE, $tokenList->consumeString()];
            case Keyword::COMMENT:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::COMMENT, $tokenList->consumeString()];
            case Keyword::COMPRESSION:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::COMPRESSION, TableCompression::get($tokenList->consumeString())];
            case Keyword::CONNECTION:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::CONNECTION, $tokenList->consumeString()];
            case Keyword::DATA:
                $tokenList->consumeKeyword(Keyword::DIRECTORY);
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::DATA_DIRECTORY, $tokenList->consumeString()];
            case Keyword::DEFAULT:
                if ($tokenList->mayConsumeKeyword(Keyword::CHARACTER)) {
                    $tokenList->consumeKeyword(Keyword::SET);
                    $tokenList->mayConsumeOperator(Operator::EQUAL);

                    return [TableOption::CHARACTER_SET, Charset::get($tokenList->consumeString())];
                } else {
                    $tokenList->consumeKeyword(Keyword::COLLATE);
                    $tokenList->mayConsumeOperator(Operator::EQUAL);

                    return [TableOption::COLLATE, $tokenList->consumeString()];
                }
            case Keyword::DELAY_KEY_WRITE:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::DELAY_KEY_WRITE, $tokenList->consumeBool()];
            case Keyword::ENCRYPTION:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::ENCRYPTION, $tokenList->consumeBool()];
            case Keyword::ENGINE:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::ENGINE, StorageEngine::get($tokenList->consumeNameOrString())];
            case Keyword::INDEX:
                $tokenList->consumeKeyword(Keyword::DIRECTORY);
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::INDEX_DIRECTORY, $tokenList->consumeString()];
            case Keyword::INSERT_METHOD:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::INSERT_METHOD, $tokenList->consumeKeywordEnum(TableInsertMethod::class)];
            case Keyword::KEY_BLOCK_SIZE:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::KEY_BLOCK_SIZE, $tokenList->consumeInt()];
            case Keyword::MAX_ROWS:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::MAX_ROWS, $tokenList->consumeInt()];
            case Keyword::MIN_ROWS:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::MIN_ROWS, $tokenList->consumeInt()];
            case Keyword::PACK_KEYS:
                $tokenList->mayConsumeOperator(Operator::EQUAL);
                if ($tokenList->mayConsumeKeyword(Keyword::DEFAULT)) {
                    return [TableOption::PACK_KEYS, ThreeStateValue::get(ThreeStateValue::DEFAULT)];
                } else {
                    return [TableOption::PACK_KEYS, ThreeStateValue::get((string) $tokenList->consumeInt())];
                }
            case Keyword::PASSWORD:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::PASSWORD, $tokenList->consumeString()];
            case Keyword::ROW_FORMAT:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::ROW_FORMAT, $tokenList->consumeKeywordEnum(TableRowFormat::class)];
            case Keyword::STATS_AUTO_RECALC:
                $tokenList->mayConsumeOperator(Operator::EQUAL);
                if ($tokenList->mayConsumeKeyword(Keyword::DEFAULT)) {
                    return [TableOption::STATS_AUTO_RECALC, ThreeStateValue::get(ThreeStateValue::DEFAULT)];
                } else {
                    return [TableOption::STATS_AUTO_RECALC, ThreeStateValue::get((string) $tokenList->consumeInt())];
                }
            case Keyword::STATS_PERSISTENT:
                $tokenList->mayConsumeOperator(Operator::EQUAL);
                if ($tokenList->mayConsumeKeyword(Keyword::DEFAULT)) {
                    return [TableOption::STATS_PERSISTENT, ThreeStateValue::get(ThreeStateValue::DEFAULT)];
                } else {
                    return [TableOption::STATS_PERSISTENT, ThreeStateValue::get((string) $tokenList->consumeInt())];
                }
            case Keyword::STATS_SAMPLE_PAGES:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::STATS_SAMPLE_PAGES, $tokenList->consumeInt()];
            case Keyword::TABLESPACE:
                $tokenList->mayConsumeOperator(Operator::EQUAL);

                return [TableOption::TABLESPACE, $tokenList->consumeNameOrString()];
            case Keyword::UNION:
                $tokenList->mayConsumeOperator(Operator::EQUAL);
                $tokenList->consume(TokenType::LEFT_PARENTHESIS);
                $tables = [];
                do {
                    $tables[] = new QualifiedName(...$tokenList->consumeQualifiedName());
                } while ($tokenList->mayConsumeComma());
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

                return [TableOption::UNION, $tables];
            default:
                return [null, null];
        }
    }

    /**
     * partition_options:
     *     PARTITION BY
     *         { [LINEAR] HASH(expr)
     *         | [LINEAR] KEY [ALGORITHM={1|2}] (column_list)
     *         | RANGE{(expr) | COLUMNS(column_list)}
     *         | LIST{(expr) | COLUMNS(column_list)}
     *         }
     *     [PARTITIONS num]
     *     [SUBPARTITION BY
     *         { [LINEAR] HASH(expr)
     *         | [LINEAR] KEY [ALGORITHM={1|2}] (column_list) }
     *         [SUBPARTITIONS num]
     *     ]
     *     [(partition_definition [, partition_definition] ...)]
     *
     * @param TokenList $tokenList
     * @return PartitioningDefinition
     */
    private function parsePartitioning(TokenList $tokenList): PartitioningDefinition
    {
        $tokenList->consumeKeywords(Keyword::PARTITION, Keyword::BY);
        $condition = $this->parsePartitionCondition($tokenList);

        $partitionsNumber = null;
        if ($tokenList->mayConsumeKeyword(Keyword::PARTITIONS)) {
            $partitionsNumber = $tokenList->consumeInt();
        }
        $subpartitionsCondition = $subpartitionsNumber = null;
        if ($tokenList->mayConsumeKeywords(Keyword::SUBPARTITION, Keyword::BY)) {
            $subpartitionsCondition = $this->parsePartitionCondition($tokenList, true);
            if ($tokenList->mayConsumeKeyword(Keyword::SUBPARTITIONS)) {
                $subpartitionsNumber = $tokenList->consumeInt();
            }
        }
        $partitions = null;
        if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
            $partitions = [];
            do {
                $partitions[] = $this->parsePartitionDefinition($tokenList);
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }

        return new PartitioningDefinition($condition, $partitions, $partitionsNumber, $subpartitionsCondition, $subpartitionsNumber);
    }

    /**
     * condition:
     *     [LINEAR] HASH(expr)
     *   | [LINEAR] KEY [ALGORITHM={1|2}] (column_list)
     *   | RANGE{(expr) | COLUMNS(column_list)}
     *   | LIST{(expr) | COLUMNS(column_list)}
     *
     * @param TokenList $tokenList
     * @param bool $subpartition
     * @return PartitioningCondition
     */
    private function parsePartitionCondition(TokenList $tokenList, bool $subpartition = false): PartitioningCondition
    {
        $linear = (bool) $tokenList->mayConsumeKeyword(Keyword::LINEAR);
        if ($linear || $subpartition) {
            $keywords = [Keyword::HASH, Keyword::KEY];
        } else {
            $keywords = [Keyword::HASH, Keyword::KEY, Keyword::RANGE, Keyword::LIST];
        }
        $keyword = $tokenList->consumeAnyKeyword(...$keywords);
        if ($keyword === Keyword::HASH) {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $expression = $this->expressionParser->parseExpression($tokenList);
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            $type = PartitioningConditionType::get($linear ? PartitioningConditionType::LINEAR_HASH : PartitioningConditionType::HASH);
            $condition = new PartitioningCondition($type, $expression);
        } elseif ($keyword === Keyword::KEY) {
            $algorithm = null;
            if ($tokenList->mayConsumeKeyword(Keyword::ALGORITHM)) {
                $tokenList->consumeOperator(Operator::EQUAL);
                $algorithm = $tokenList->consumeInt();
            }
            $columns = $this->parseColumnList($tokenList);
            $type = PartitioningConditionType::get($linear ? PartitioningConditionType::LINEAR_KEY : PartitioningConditionType::KEY);
            $condition = new PartitioningCondition($type, null, $columns, $algorithm);
        } elseif ($keyword === Keyword::RANGE) {
            $type = PartitioningConditionType::get(PartitioningConditionType::RANGE);
            if ($tokenList->mayConsumeKeyword(Keyword::COLUMNS)) {
                $columns = $this->parseColumnList($tokenList);
                $condition = new PartitioningCondition($type, null, $columns);
            } else {
                $tokenList->mayConsume(TokenType::LEFT_PARENTHESIS);
                $expression = $this->expressionParser->parseExpression($tokenList);
                $tokenList->mayConsume(TokenType::RIGHT_PARENTHESIS);
                $condition = new PartitioningCondition($type, $expression);
            }
        } else {
            $type = PartitioningConditionType::get(PartitioningConditionType::LIST);
            if ($tokenList->mayConsumeKeyword(Keyword::COLUMNS)) {
                $columns = $this->parseColumnList($tokenList);
                $condition = new PartitioningCondition($type, null, $columns);
            } else {
                $tokenList->mayConsume(TokenType::LEFT_PARENTHESIS);
                $expression = $this->expressionParser->parseExpression($tokenList);
                $tokenList->mayConsume(TokenType::RIGHT_PARENTHESIS);
                $condition = new PartitioningCondition($type, $expression);
            }
        }

        return $condition;
    }

    /**
     * partition_definition:
     *     PARTITION partition_name
     *         [VALUES
     *             {LESS THAN {(expr | value_list) | MAXVALUE}
     *             | IN (value_list)}]
     *         [[STORAGE] ENGINE [=] engine_name]
     *         [COMMENT [=] 'comment_text' ]
     *         [DATA DIRECTORY [=] 'data_dir']
     *         [INDEX DIRECTORY [=] 'index_dir']
     *         [MAX_ROWS [=] max_number_of_rows]
     *         [MIN_ROWS [=] min_number_of_rows]
     *         [TABLESPACE [=] tablespace_name]
     *         [(subpartition_definition [, subpartition_definition] ...)]
     *
     * subpartition_definition:
     *     SUBPARTITION logical_name
     *         [[STORAGE] ENGINE [=] engine_name]
     *         [COMMENT [=] 'comment_text' ]
     *         [DATA DIRECTORY [=] 'data_dir']
     *         [INDEX DIRECTORY [=] 'index_dir']
     *         [MAX_ROWS [=] max_number_of_rows]
     *         [MIN_ROWS [=] min_number_of_rows]
     *         [TABLESPACE [=] tablespace_name]
     *
     * @param TokenList $tokenList
     * @return PartitionDefinition
     */
    private function parsePartitionDefinition(TokenList $tokenList): PartitionDefinition
    {
        $tokenList->consumeKeyword(Keyword::PARTITION);
        $name = $tokenList->consumeName();

        $lessThan = $values = null;
        if ($tokenList->mayConsumeKeyword(Keyword::VALUES)) {
            if ($tokenList->mayConsumeKeywords(Keyword::LESS, Keyword::THAN)) {
                if ($tokenList->mayConsumeKeyword(Keyword::MAXVALUE)) {
                    $lessThan = PartitionDefinition::MAX_VALUE;
                } else {
                    $tokenList->consume(TokenType::LEFT_PARENTHESIS);
                    if ($tokenList->seek(TokenType::COMMA, 2)) {
                        $lessThan = [];
                        do {
                            $lessThan[] = $this->expressionParser->parseLiteralValue($tokenList);
                            if (!$tokenList->mayConsume(TokenType::COMMA)) {
                                break;
                            }
                        } while (true);
                    } else {
                        $lessThan = $this->expressionParser->parseExpression($tokenList);
                    }
                    $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
                }
            } else {
                $tokenList->consumeKeyword(Keyword::IN);
                $tokenList->consume(TokenType::LEFT_PARENTHESIS);
                $values = [];
                do {
                    $values[] = $this->expressionParser->parseLiteralValue($tokenList);
                } while ($tokenList->mayConsumeComma());
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            }
        }

        $options = $this->parsePartitionOptions($tokenList);

        $subpartitions = null;
        if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
            $subpartitions = [];
            do {
                $tokenList->consumeKeyword(Keyword::SUBPARTITION);
                $subName = $tokenList->consumeName();
                $subOptions = $this->parsePartitionOptions($tokenList);
                $subpartitions[$subName] = $subOptions;
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }

        return new PartitionDefinition($name, $lessThan, $values, $options, $subpartitions);
    }

    /**
     * options:
     *     [[STORAGE] ENGINE [=] engine_name]
     *     [COMMENT [=] 'comment_text' ]
     *     [DATA DIRECTORY [=] 'data_dir']
     *     [INDEX DIRECTORY [=] 'index_dir']
     *     [MAX_ROWS [=] max_number_of_rows]
     *     [MIN_ROWS [=] min_number_of_rows]
     *     [TABLESPACE [=] tablespace_name]
     *
     * @param TokenList $tokenList
     * @return mixed[]
     */
    private function parsePartitionOptions(TokenList $tokenList): ?array
    {
        $options = [];

        if ($tokenList->mayConsumeKeyword(Keyword::STORAGE)) {
            $tokenList->consumeKeyword(Keyword::ENGINE);
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[PartitionOption::ENGINE] = $tokenList->consumeNameOrString();
        } elseif ($tokenList->mayConsumeKeyword(Keyword::ENGINE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[PartitionOption::ENGINE] = $tokenList->consumeNameOrString();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::COMMENT)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[PartitionOption::COMMENT] = $tokenList->consumeString();
        }
        if ($tokenList->mayConsumeKeywords(Keyword::DATA, Keyword::DIRECTORY)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[PartitionOption::DATA_DIRECTORY] = $tokenList->consumeString();
        }
        if ($tokenList->mayConsumeKeywords(Keyword::INDEX, Keyword::DIRECTORY)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[PartitionOption::INDEX_DIRECTORY] = $tokenList->consumeString();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::MAX_ROWS)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[PartitionOption::MAX_ROWS] = $tokenList->consumeInt();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::MIN_ROWS)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[PartitionOption::MIN_ROWS] = $tokenList->consumeInt();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::TABLESPACE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[PartitionOption::TABLESPACE] = $tokenList->consumeString();
        }

        return $options ?: null;
    }

    /**
     * @param TokenList $tokenList
     * @return string[]|null
     */
    private function parsePartitionNames(TokenList $tokenList): ?array
    {
        if ($tokenList->mayConsumeKeyword(Keyword::ALL)) {
            return null;
        }
        $names = [];
        do {
            $names[] = $tokenList->consumeName();
        } while ($tokenList->mayConsumeComma());

        return $names;
    }

    /**
     * @param TokenList $tokenList
     * @return string[]
     */
    private function parseColumnList(TokenList $tokenList): array
    {
        $columns = [];
        $tokenList->consume(TokenType::LEFT_PARENTHESIS);
        do {
            $columns[] = $tokenList->consumeName();
        } while ($tokenList->mayConsumeComma());
        $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

        return $columns;
    }

    /**
     * DROP [TEMPORARY] TABLE [IF EXISTS]
     *     tbl_name [, tbl_name] ...
     *     [RESTRICT | CASCADE]
     *
     * @param TokenList $tokenList
     * @return DropTableCommand
     */
    public function parseDropTable(TokenList $tokenList): DropTableCommand
    {
        $tokenList->consumeKeyword(Keyword::DROP);
        $temporary = (bool) $tokenList->mayConsumeKeyword(Keyword::TEMPORARY);
        $tokenList->consumeKeyword(Keyword::TABLE);
        $ifExists = (bool) $tokenList->mayConsumeKeyword(Keyword::IF);
        if ($ifExists) {
            $tokenList->consumeKeyword(Keyword::EXISTS);
        }
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->consumeQualifiedName());
        } while ($tokenList->mayConsumeComma());

        // ignored in MySQL 5.7, 8.0
        $cascadeRestrict = $tokenList->mayConsumeAnyKeyword(Keyword::CASCADE, Keyword::RESTRICT);
        $cascadeRestrict = $cascadeRestrict === Keyword::CASCADE ? true : ($cascadeRestrict === Keyword::RESTRICT ? false : null);
        $tokenList->expectEnd();

        return new DropTableCommand($tables, $temporary, $ifExists, $cascadeRestrict);
    }

    /**
     * RENAME TABLE tbl_name TO new_tbl_name
     *     [, tbl_name2 TO new_tbl_name2] ...
     *
     * @param TokenList $tokenList
     * @return RenameTableCommand
     */
    public function parseRenameTable(TokenList $tokenList): RenameTableCommand
    {
        $tokenList->consumeKeywords(Keyword::RENAME, Keyword::TABLE);

        $tables = [];
        $newTables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->consumeQualifiedName());
            $tokenList->consumeKeyword(Keyword::TO);
            $newTables[] = new QualifiedName(...$tokenList->consumeQualifiedName());
        } while ($tokenList->mayConsumeComma());
        $tokenList->expectEnd();

        return new RenameTableCommand($tables, $newTables);
    }

    /**
     * TRUNCATE [TABLE] tbl_name
     *
     * @param TokenList $tokenList
     * @return TruncateTableCommand
     */
    public function parseTruncateTable(TokenList $tokenList): TruncateTableCommand
    {
        $tokenList->consumeKeyword(Keyword::TRUNCATE);
        $tokenList->mayConsumeKeyword(Keyword::TABLE);
        $table = new QualifiedName(...$tokenList->consumeQualifiedName());
        $tokenList->expectEnd();

        return new TruncateTableCommand($table);
    }

}
