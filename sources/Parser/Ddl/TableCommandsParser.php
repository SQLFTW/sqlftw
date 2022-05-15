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
use SqlFtw\Parser\Dml\QueryParser;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Charset;
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
use SqlFtw\Sql\Expression\DataType;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\ValueLiteral;
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

    /** @var QueryParser */
    private $queryParser;

    public function __construct(
        TypeParser $typeParser,
        ExpressionParser $expressionParser,
        IndexCommandsParser $indexCommandsParser,
        QueryParser $queryParser
    )
    {
        $this->typeParser = $typeParser;
        $this->expressionParser = $expressionParser;
        $this->indexCommandsParser = $indexCommandsParser;
        $this->queryParser = $queryParser;
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
     *   | ORDER BY col_name [ASC | DESC] [, col_name [ASC | DESC]] ...
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
     */
    public function parseAlterTable(TokenList $tokenList): AlterTableCommand
    {
        $tokenList->expectKeywords(Keyword::ALTER, Keyword::TABLE);
        $name = new QualifiedName(...$tokenList->expectQualifiedName());

        $actions = [];
        $alterOptions = [];
        $tableOptions = [];
        do {
            $position = $tokenList->getPosition();
            $keyword = $tokenList->expect(TokenType::KEYWORD)->value;
            switch ($keyword) {
                case Keyword::ADD:
                    $second = $tokenList->get(TokenType::KEYWORD);
                    $secondValue = $second !== null ? $second->value : null;
                    switch ($secondValue) {
                        case null:
                        case Keyword::COLUMN:
                            if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
                                // ADD [COLUMN] (col_name column_definition, ...)
                                $addColumns = [];
                                do {
                                    $addColumns[] = $this->parseColumn($tokenList);
                                } while ($tokenList->hasComma());
                                $actions[] = new AddColumnsAction($addColumns);
                                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
                            } else {
                                // ADD [COLUMN] col_name column_definition [FIRST | AFTER col_name ]
                                $column = $this->parseColumn($tokenList);
                                $after = null;
                                if ($tokenList->hasKeyword(Keyword::FIRST)) {
                                    $after = ModifyColumnAction::FIRST;
                                } elseif ($tokenList->hasKeyword(Keyword::AFTER)) {
                                    $after = $tokenList->expectName();
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
                            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
                            $partition = $this->parsePartitionDefinition($tokenList);
                            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
                            $actions[] = new AddPartitionAction($partition);
                            break;
                        default:
                            // keyword used as a column name
                            if ($second !== null && ($second->type & TokenType::RESERVED) === 0) {
                                // ADD [COLUMN] col_name column_definition [FIRST | AFTER col_name ]
                                $column = $this->parseColumn($tokenList->resetPosition(-1));
                                $after = null;
                                if ($tokenList->hasKeyword(Keyword::FIRST)) {
                                    $after = ModifyColumnAction::FIRST;
                                } elseif ($tokenList->hasKeyword(Keyword::AFTER)) {
                                    $after = $tokenList->expectName();
                                }
                                $actions[] = new AddColumnAction($column, $after);
                            } else {
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
                    }
                    break;
                case Keyword::ALGORITHM:
                    // ALGORITHM [=] {DEFAULT|INPLACE|COPY}
                    $tokenList->passEqual();
                    $alterOptions[Keyword::ALGORITHM] = $tokenList->expectKeywordEnum(AlterTableAlgorithm::class);
                    break;
                case Keyword::ALTER:
                    if ($tokenList->hasKeyword(Keyword::INDEX)) {
                        // ALTER INDEX index_name {VISIBLE | INVISIBLE}
                        $index = $tokenList->expectName();
                        $visible = $tokenList->expectAnyKeyword(Keyword::VISIBLE, Keyword::INVISIBLE);
                        $actions[] = new AlterIndexAction($index, $visible === Keyword::VISIBLE);
                    } elseif ($tokenList->hasKeyword(Keyword::CONSTRAINT)) {
                        // ALTER CONSTRAINT symbol [NOT] ENFORCED
                        $constraint = $tokenList->expectName();
                        $enforced = true;
                        if ($tokenList->hasKeyword(Keyword::NOT)) {
                            $enforced = false;
                        }
                        $tokenList->expectKeyword(Keyword::ENFORCED);
                        $actions[] = new AlterConstraintAction($constraint, $enforced);
                    } elseif ($tokenList->hasKeyword(Keyword::CHECK)) {
                        // ALTER CHECK symbol [NOT] ENFORCED
                        $check = $tokenList->expectName();
                        $enforced = true;
                        if ($tokenList->hasKeyword(Keyword::NOT)) {
                            $enforced = false;
                        }
                        $tokenList->expectKeyword(Keyword::ENFORCED);
                        $actions[] = new AlterCheckAction($check, $enforced);
                    } else {
                        // ALTER [COLUMN] col_name {SET DEFAULT literal | DROP DEFAULT}
                        $tokenList->passKeyword(Keyword::COLUMN);
                        $column = $tokenList->expectName();
                        if ($tokenList->hasKeywords(Keyword::SET, Keyword::DEFAULT)) {
                            $value = $this->expressionParser->parseLiteralValue($tokenList);
                            $actions[] = new AlterColumnAction($column, $value);
                        } else {
                            $tokenList->expectKeywords(Keyword::DROP, Keyword::DEFAULT);
                            $actions[] = new AlterColumnAction($column, null);
                        }
                    }
                    break;
                case Keyword::ANALYZE:
                    // ANALYZE PARTITION {partition_names | ALL}
                    $tokenList->expectKeyword(Keyword::PARTITION);
                    $partitions = $this->parsePartitionNames($tokenList);
                    $actions[] = new AnalyzePartitionAction($partitions);
                    break;
                case Keyword::CHANGE:
                    // CHANGE [COLUMN] old_col_name new_col_name column_definition [FIRST|AFTER col_name]
                    $tokenList->passKeyword(Keyword::COLUMN);
                    $oldName = $tokenList->expectName();
                    $column = $this->parseColumn($tokenList);
                    $after = null;
                    if ($tokenList->hasKeyword(Keyword::FIRST)) {
                        $after = ModifyColumnAction::FIRST;
                    } elseif ($tokenList->hasKeyword(Keyword::AFTER)) {
                        $after = $tokenList->expectName();
                    }
                    $actions[] = new ChangeColumnAction($oldName, $column, $after);
                    break;
                case Keyword::CHECK:
                    // CHECK PARTITION {partition_names | ALL}
                    $tokenList->expectKeyword(Keyword::PARTITION);
                    $partitions = $this->parsePartitionNames($tokenList);
                    $actions[] = new CheckPartitionAction($partitions);
                    break;
                case Keyword::COALESCE:
                    // COALESCE PARTITION number
                    $tokenList->expectKeyword(Keyword::PARTITION);
                    $actions[] = new CoalescePartitionAction($tokenList->expectInt());
                    break;
                case Keyword::CONVERT:
                    // CONVERT TO CHARACTER SET charset_name [COLLATE collation_name]
                    $tokenList->expectKeyword(Keyword::TO);
                    if (!$tokenList->hasKeyword(Keyword::CHARSET)) {
                        $tokenList->expectKeywords(Keyword::CHARACTER, Keyword::SET);
                    }
                    /** @var Charset $charset */
                    $charset = $tokenList->expectCharsetName();
                    $collation = null;
                    if ($tokenList->hasKeyword(Keyword::COLLATE)) {
                        $collation = $tokenList->expectCollationName();
                    }
                    $actions[] = new ConvertToCharsetAction($charset, $collation);
                    break;
                case Keyword::DISCARD:
                    $second = $tokenList->expectAnyKeyword(Keyword::TABLESPACE, Keyword::PARTITION);
                    if ($second === Keyword::TABLESPACE) {
                        // DISCARD TABLESPACE
                        $actions[] = new DiscardTablespaceAction();
                    } else {
                        // DISCARD PARTITION {partition_names | ALL} TABLESPACE
                        $partitions = $this->parsePartitionNames($tokenList);
                        $tokenList->expectKeyword(Keyword::TABLESPACE);
                        $actions[] = new DiscardPartitionTablespaceAction($partitions);
                    }
                    break;
                case Keyword::DISABLE:
                    // DISABLE KEYS
                    $tokenList->expectKeyword(Keyword::KEYS);
                    $actions[] = new DisableKeysAction();
                    break;
                case Keyword::DROP:
                    $second = $tokenList->get(TokenType::KEYWORD);
                    $secondValue = $second !== null ? $second->value : null;
                    switch ($secondValue) {
                        case Keyword::INDEX:
                        case Keyword::KEY:
                            // DROP {INDEX|KEY} index_name
                            $actions[] = new DropIndexAction($tokenList->expectName());
                            break;
                        case Keyword::FOREIGN:
                            // DROP FOREIGN KEY fk_symbol
                            $tokenList->expectKeyword(Keyword::KEY);
                            $actions[] = new DropForeignKeyAction($tokenList->expectName());
                            break;
                        case Keyword::CONSTRAINT:
                            // DROP CONSTRAINT symbol
                            $actions[] = new DropConstraintAction($tokenList->expectName());
                            break;
                        case Keyword::CHECK:
                            // DROP CHECK symbol
                            $actions[] = new DropCheckAction($tokenList->expectName());
                            break;
                        case Keyword::PARTITION:
                            // DROP PARTITION partition_names
                            $partitions = $this->parsePartitionNames($tokenList);
                            if ($partitions === null) {
                                $tokenList->expected('Expected specific partition names, found "ALL".');
                            }
                            $actions[] = new DropPartitionAction($partitions);
                            break;
                        case Keyword::PRIMARY:
                            // DROP PRIMARY KEY
                            $tokenList->expectKeyword(Keyword::KEY);
                            $actions[] = new DropPrimaryKeyAction();
                            break;
                        case null:
                        case Keyword::COLUMN:
                            // DROP [COLUMN] col_name
                            $tokenList->passKeyword(Keyword::COLUMN);
                            $actions[] = new DropColumnAction($tokenList->expectName());
                            break;
                        default:
                            if ($second !== null && ($second->type & TokenType::UNQUOTED_NAME) !== 0) {
                                // DROP [COLUMN] col_name
                                /** @var string $columnName */
                                $columnName = $second->original;
                                $actions[] = new DropColumnAction($columnName);
                            } else {
                                $tokenList->expectedAnyKeyword(Keyword::COLUMN, Keyword::INDEX, Keyword::KEY, Keyword::FOREIGN, Keyword::PARTITION, Keyword::PRIMARY);
                            }
                    }
                    break;
                case Keyword::ENABLE:
                    // ENABLE KEYS
                    $tokenList->expectKeyword(Keyword::KEYS);
                    $actions[] = new EnableKeysAction();
                    break;
                case Keyword::EXCHANGE:
                    // EXCHANGE PARTITION partition_name WITH TABLE tbl_name [{WITH|WITHOUT} VALIDATION]
                    $tokenList->expectKeyword(Keyword::PARTITION);
                    $partition = $tokenList->expectName();
                    $tokenList->expectKeywords(Keyword::WITH, Keyword::TABLE);
                    $table = new QualifiedName(...$tokenList->expectQualifiedName());
                    $validation = $tokenList->getAnyKeyword(Keyword::WITH, Keyword::WITHOUT);
                    if ($validation === Keyword::WITH) {
                        $tokenList->expectKeyword(Keyword::VALIDATION);
                        $validation = true;
                    } elseif ($validation === Keyword::WITHOUT) {
                        $tokenList->expectKeyword(Keyword::VALIDATION);
                        $validation = false;
                    } else {
                        $validation = null;
                    }
                    $actions[] = new ExchangePartitionAction($partition, $table, $validation);
                    break;
                case Keyword::FORCE:
                    // FORCE
                    $alterOptions[AlterTableOption::FORCE] = true;
                    break;
                case Keyword::IMPORT:
                    $second = $tokenList->expectAnyKeyword(Keyword::TABLESPACE, Keyword::PARTITION);
                    if ($second === Keyword::TABLESPACE) {
                        // IMPORT TABLESPACE
                        $actions[] = new ImportTablespaceAction();
                    } else {
                        // IMPORT PARTITION {partition_names | ALL} TABLESPACE
                        $partitions = $this->parsePartitionNames($tokenList);
                        $tokenList->expectKeyword(Keyword::TABLESPACE);
                        $actions[] = new ImportPartitionTablespaceAction($partitions);
                    }
                    break;
                case Keyword::LOCK:
                    // LOCK [=] {DEFAULT|NONE|SHARED|EXCLUSIVE}
                    $tokenList->passEqual();
                    $alterOptions[Keyword::LOCK] = $tokenList->expectKeywordEnum(AlterTableLock::class);
                    break;
                case Keyword::MODIFY:
                    // MODIFY [COLUMN] col_name column_definition [FIRST | AFTER col_name]
                    $tokenList->passKeyword(Keyword::COLUMN);
                    $column = $this->parseColumn($tokenList);
                    $after = null;
                    if ($tokenList->hasKeyword(Keyword::FIRST)) {
                        $after = ModifyColumnAction::FIRST;
                    } elseif ($tokenList->hasKeyword(Keyword::AFTER)) {
                        $after = $tokenList->expectName();
                    }
                    $actions[] = new ModifyColumnAction($column, $after);
                    break;
                case Keyword::OPTIMIZE:
                    // OPTIMIZE PARTITION {partition_names | ALL}
                    $tokenList->expectKeyword(Keyword::PARTITION);
                    $partitions = $this->parsePartitionNames($tokenList);
                    $actions[] = new OptimizePartitionAction($partitions);
                    break;
                case Keyword::ORDER:
                    // ORDER BY col_name [, col_name] ...
                    $tokenList->expectKeyword(Keyword::BY);
                    $columns = [];
                    do {
                        $column = $tokenList->expectName();
                        /** @var 'ASC'|'DESC'|null $order */
                        $order = $tokenList->getAnyKeyword(Keyword::ASC, Keyword::DESC);
                        $columns[$column] = $order;
                    } while ($tokenList->hasComma());
                    $actions[] = new OrderByAction($columns);
                    break;
                case Keyword::REBUILD:
                    // REBUILD PARTITION {partition_names | ALL}
                    $tokenList->expectKeyword(Keyword::PARTITION);
                    $partitions = $this->parsePartitionNames($tokenList);
                    $actions[] = new RebuildPartitionAction($partitions);
                    break;
                case Keyword::REMOVE:
                    // REMOVE PARTITIONING
                    $tokenList->expectKeyword(Keyword::PARTITIONING);
                    $actions[] = new RemovePartitioningAction();
                    break;
                case Keyword::RENAME:
                    if ($tokenList->hasAnyKeyword(Keyword::INDEX, Keyword::KEY)) {
                        // RENAME {INDEX|KEY} old_index_name TO new_index_name
                        $oldName = $tokenList->expectName();
                        $tokenList->expectKeyword(Keyword::TO);
                        $newName = $tokenList->expectName();
                        $actions[] = new RenameIndexAction($oldName, $newName);
                    } else {
                        // RENAME [TO|AS] new_tbl_name
                        $tokenList->getAnyKeyword(Keyword::TO, Keyword::AS);
                        $newName = new QualifiedName(...$tokenList->expectQualifiedName());
                        $actions[] = new RenameToAction($newName);
                    }
                    break;
                case Keyword::REORGANIZE:
                    // REORGANIZE PARTITION partition_names INTO (partition_definitions, ...)
                    $tokenList->expectKeyword(Keyword::PARTITION);
                    $oldPartitions = $this->parsePartitionNames($tokenList);
                    if ($oldPartitions === null) {
                        $tokenList->expected('Expected specific partition names, found "ALL".');
                    }
                    $tokenList->expectKeyword(Keyword::INTO);
                    $tokenList->expect(TokenType::LEFT_PARENTHESIS);
                    $newPartitions = [];
                    do {
                        $newPartitions[] = $this->parsePartitionDefinition($tokenList);
                    } while ($tokenList->hasComma());
                    $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
                    $actions[] = new ReorganizePartitionAction($oldPartitions, $newPartitions);
                    break;
                case Keyword::REPAIR:
                    // REPAIR PARTITION {partition_names | ALL}
                    $tokenList->expectKeyword(Keyword::PARTITION);
                    $partitions = $this->parsePartitionNames($tokenList);
                    $actions[] = new RepairPartitionAction($partitions);
                    break;
                case Keyword::TRUNCATE:
                    // TRUNCATE PARTITION {partition_names | ALL}
                    $tokenList->expectKeyword(Keyword::PARTITION);
                    $partitions = $this->parsePartitionNames($tokenList);
                    $actions[] = new TruncatePartitionAction($partitions);
                    break;
                case Keyword::UPGRADE:
                    // UPGRADE PARTITIONING
                    $tokenList->expectKeyword(Keyword::PARTITIONING);
                    $actions[] = new UpgradePartitioningAction();
                    break;
                case Keyword::WITH:
                    // {WITHOUT|WITH} VALIDATION
                    $tokenList->expectKeyword(Keyword::VALIDATION);
                    $alterOptions[Keyword::VALIDATION] = true;
                    break;
                case Keyword::WITHOUT:
                    // {WITHOUT|WITH} VALIDATION
                    $tokenList->expectKeyword(Keyword::VALIDATION);
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
                    $position = $tokenList->getPosition();
                    $trailingComma = $tokenList->hasComma();
                    do {
                        [$option, $value] = $this->parseTableOption($tokenList->resetPosition($position));
                        if ($option === null) {
                            break;
                        }
                        $tableOptions[$option] = $value;
                        $position = $tokenList->getPosition();
                        $trailingComma = $tokenList->hasComma();
                    } while (true);
                    if ($trailingComma) {
                        $tokenList->resetPosition($position);
                    }
            }
        } while ($tokenList->hasComma());

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
     */
    public function parseCreateTable(TokenList $tokenList): AnyCreateTableCommand
    {
        $tokenList->expectKeyword(Keyword::CREATE);
        $temporary = $tokenList->hasKeyword(Keyword::TEMPORARY);
        $tokenList->expectKeyword(Keyword::TABLE);
        $ifNotExists = $tokenList->hasKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);
        $table = new QualifiedName(...$tokenList->expectQualifiedName());

        $position = $tokenList->getPosition();
        $bodyOpen = $tokenList->get(TokenType::LEFT_PARENTHESIS);
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $oldTable = new QualifiedName(...$tokenList->expectQualifiedName());
            if ($bodyOpen !== null) {
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            }

            return new CreateTableLikeCommand($table, $oldTable, $temporary, $ifNotExists);
        }

        $items = [];
        if ($bodyOpen !== null) {
            $items = $this->parseCreateTableBody($tokenList->resetPosition($position));
        }

        $options = [];
        $trailingComma = false;
        $position = $tokenList->getPosition();
        do {
            [$option, $value] = $this->parseTableOption($tokenList);
            if ($option === null) {
                break;
            }
            $options[$option] = $value;
            $position = $tokenList->getPosition();
            $trailingComma = $tokenList->hasComma();
        } while (true);
        if ($trailingComma) {
            $tokenList->resetPosition($position);
        }

        $partitioning = null;
        if ($tokenList->hasKeyword(Keyword::PARTITION)) {
            $partitioning = $this->parsePartitioning($tokenList->resetPosition(-1));
        }

        /** @var DuplicateOption|null $duplicateOption */
        $duplicateOption = $tokenList->getKeywordEnum(DuplicateOption::class);
        $select = null;

        if ($tokenList->hasKeyword(Keyword::AS) || $items === [] || $duplicateOption !== null) {
            $select = $this->queryParser->parseQuery($tokenList);
        } elseif (!$tokenList->isFinished()) {
            $position = $tokenList->getPosition();
            if (!$tokenList->has(TokenType::SEMICOLON)) {
                $select = $this->queryParser->parseQuery($tokenList);
            } else {
                $tokenList->resetPosition($position);
            }
        }

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
     * @return TableItem[]
     */
    private function parseCreateTableBody(TokenList $tokenList): array
    {
        $items = [];
        $tokenList->expect(TokenType::LEFT_PARENTHESIS);

        do {
            if ($tokenList->hasKeyword(Keyword::CHECK)) {
                $items[] = $this->parseCheck($tokenList);
            } elseif ($tokenList->hasAnyKeyword(Keyword::INDEX, Keyword::KEY, Keyword::FULLTEXT, Keyword::SPATIAL, Keyword::UNIQUE)) {
                $items[] = $this->parseIndex($tokenList->resetPosition(-1));
            } elseif ($tokenList->hasKeyword(Keyword::PRIMARY)) {
                $items[] = $this->parseIndex($tokenList, true);
            } elseif ($tokenList->hasKeyword(Keyword::FOREIGN)) {
                $items[] = $this->parseForeignKey($tokenList->resetPosition(-1));
            } elseif ($tokenList->hasKeyword(Keyword::CONSTRAINT)) {
                $items[] = $this->parseConstraint($tokenList->resetPosition(-1));
            } else {
                $items[] = $this->parseColumn($tokenList);
            }
        } while ($tokenList->hasComma());

        $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

        return $items;
    }

    /**
     * create_definition:
     *     col_name column_definition
     *
     * column_definition:
     *     data_type [NOT NULL | NULL] [DEFAULT default_value]
     *       [VISIBLE | INVISIBLE]
     *       [AUTO_INCREMENT] [UNIQUE [KEY] | [PRIMARY] KEY]
     *       [COMMENT 'string']
     *       [COLUMN_FORMAT {FIXED|DYNAMIC|DEFAULT}]
     *       [ON UPDATE CURRENT_TIMESTAMP[(...)]] -- not documented in main article: @see https://dev.mysql.com/doc/refman/8.0/en/timestamp-initialization.html
     *       [reference_definition]
     *       [check_constraint_definition]
     *   | data_type [GENERATED ALWAYS] AS (expression)
     *       [VIRTUAL | STORED] [UNIQUE [KEY]] [COMMENT comment]
     *       [VISIBLE | INVISIBLE]
     *       [NOT NULL | NULL] [[PRIMARY] KEY]
     */
    private function parseColumn(TokenList $tokenList): ColumnDefinition
    {
        $name = $tokenList->expectName();
        $type = $this->typeParser->parseType($tokenList);

        $keyword = $tokenList->getAnyKeyword(Keyword::GENERATED, Keyword::AS);
        if ($keyword === null) {
            return $this->parseOrdinaryColumn($name, $type, $tokenList);
        }

        if ($keyword === Keyword::GENERATED) {
            $tokenList->expectKeywords(Keyword::ALWAYS, Keyword::AS);
        }

        return $this->parseGeneratedColumn($name, $type, $tokenList);
    }

    private function parseOrdinaryColumn(string $name, DataType $type, TokenList $tokenList): ColumnDefinition
    {
        $null = $default = $index = $comment = $columnFormat = $reference = $check = $onUpdate = $charset = $collation = $visible = null;
        $autoIncrement = false;
        // phpcs:disable Squiz.Arrays.ArrayDeclaration.ValueNoNewline
        $keywords = [Keyword::NOT, Keyword::NULL, Keyword::DEFAULT, Keyword::AUTO_INCREMENT, Keyword::ON, Keyword::UNIQUE,
            Keyword::PRIMARY, Keyword::KEY, Keyword::COMMENT, Keyword::COLUMN_FORMAT, Keyword::REFERENCES, Keyword::CHECK,
            Keyword::CHARACTER, Keyword::CHARSET, Keyword::COLLATE, Keyword::VISIBLE, Keyword::INVISIBLE];
        while (($keyword = $tokenList->getAnyKeyword(...$keywords)) !== null) {
            switch ($keyword) {
                case Keyword::NOT:
                    // [NOT NULL | NULL]
                    $tokenList->expectKeyword(Keyword::NULL);
                    $null = false;
                    break;
                case Keyword::NULL:
                    $null = true;
                    break;
                case Keyword::DEFAULT:
                    if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
                        // [DEFAULT (expr)]
                        $default = $this->expressionParser->parseExpression($tokenList);
                        $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
                    } elseif ($tokenList->hasKeyword(Keyword::CURRENT_TIMESTAMP)) {
                        // [DEFAULT CURRENT_TIMESTAMP[(...)]]
                        if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
                            $param = $tokenList->getInt();
                            $params = $param !== null ? [new ValueLiteral($param)] : [];
                            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
                            $default = new FunctionCall(new QualifiedName(Keyword::CURRENT_TIMESTAMP), $params);
                        } else {
                            $default = new Identifier(Keyword::CURRENT_TIMESTAMP);
                        }
                    } else {
                        // [DEFAULT default_value]
                        $default = $this->expressionParser->parseLiteralValue($tokenList);
                    }
                    break;
                case Keyword::VISIBLE:
                    // [VISIBLE | INVISIBLE]
                    $tokenList->check('column visibility', 80023);
                    $visible = true;
                    break;
                case Keyword::INVISIBLE:
                    // [VISIBLE | INVISIBLE]
                    $tokenList->check('column visibility', 80023);
                    $visible = false;
                    break;
                case Keyword::AUTO_INCREMENT:
                    // [AUTO_INCREMENT]
                    $autoIncrement = true;
                    break;
                case Keyword::ON:
                    // [ON UPDATE CURRENT_TIMESTAMP[(...)]]
                    $tokenList->expectKeyword(Keyword::UPDATE);
                    $tokenList->expectKeyword(Keyword::CURRENT_TIMESTAMP);
                    if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
                        $param = $tokenList->getInt();
                        $params = $param !== null ? [new ValueLiteral($param)] : [];
                        $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
                        $onUpdate = new FunctionCall(new QualifiedName(Keyword::CURRENT_TIMESTAMP), $params);
                    } else {
                        $onUpdate = new Identifier(Keyword::CURRENT_TIMESTAMP);
                    }
                case Keyword::UNIQUE:
                    // [UNIQUE [KEY] | [PRIMARY] KEY]
                    $tokenList->passKeyword(Keyword::KEY);
                    $index = IndexType::get(IndexType::UNIQUE);
                    break;
                case Keyword::PRIMARY:
                    $tokenList->expectKeyword(Keyword::KEY);
                    $index = IndexType::get(IndexType::PRIMARY);
                    break;
                case Keyword::KEY:
                    $index = IndexType::get(IndexType::INDEX);
                    break;
                case Keyword::COMMENT:
                    // [COMMENT 'string']
                    $comment = $tokenList->expectString();
                    break;
                case Keyword::COLUMN_FORMAT:
                    // [COLUMN_FORMAT {FIXED|DYNAMIC|DEFAULT}]
                    /** @var ColumnFormat $columnFormat */
                    $columnFormat = $tokenList->expectKeywordEnum(ColumnFormat::class);
                    break;
                case Keyword::REFERENCES:
                    // [reference_definition]
                    $reference = $this->parseReference($tokenList->resetPosition(-1));
                    break;
                case Keyword::CHECK:
                    // [check_constraint_definition]
                    $check = $this->parseCheck($tokenList);
                    break;
                case Keyword::CHARACTER:
                    $tokenList->expectKeyword(Keyword::SET);
                case Keyword::CHARSET:
                    $charset = $tokenList->expectCharsetName();
                    break;
                case Keyword::COLLATE:
                    $collation = $tokenList->expectCollationName();
                    break;
            }
        }
        if ($charset !== null) {
            $type = $type->addCharset($charset);
        }
        if ($collation !== null) {
            $type = $type->addCollation($collation);
        }

        return new ColumnDefinition($name, $type, $default, $null, $visible, $autoIncrement, $onUpdate, $comment, $index, $columnFormat, $reference, $check);
    }

    private function parseGeneratedColumn(string $name, DataType $type, TokenList $tokenList): ColumnDefinition
    {
        $tokenList->expect(TokenType::LEFT_PARENTHESIS);
        $expression = $this->expressionParser->parseExpression($tokenList);
        $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

        /** @var GeneratedColumnType $generatedType */
        $generatedType = $tokenList->getKeywordEnum(GeneratedColumnType::class);
        $index = $comment = $visible = $null = null;
        if ($tokenList->hasKeyword(Keyword::UNIQUE)) {
            $tokenList->passKeyword(Keyword::KEY);
            $index = IndexType::get(IndexType::UNIQUE);
        }
        if ($tokenList->hasKeyword(Keyword::COMMENT)) {
            $comment = $tokenList->expectString();
        }
        if ($tokenList->hasKeyword(Keyword::VISIBLE)) {
            $tokenList->check('column visibility', 80023);
            $visible = true;
        } elseif ($tokenList->hasKeyword(Keyword::INVISIBLE)) {
            $tokenList->check('column visibility', 80023);
            $visible = false;
        }
        if ($tokenList->hasKeywords(Keyword::NOT, Keyword::NULL)) {
            $null = false;
        } elseif ($tokenList->hasKeyword(Keyword::NULL)) {
            $null = true;
        }

        if ($tokenList->hasKeyword(Keyword::PRIMARY)) {
            $tokenList->passKeyword(Keyword::KEY);
            $index = IndexType::get(IndexType::PRIMARY);
        } elseif ($tokenList->hasKeyword(Keyword::KEY)) {
            $index = IndexType::get(IndexType::INDEX);
        }

        return ColumnDefinition::createGenerated($name, $type, $expression, $generatedType, $null, $visible, $comment, $index);
    }

    /**
     * check_constraint_definition:
     *     [CONSTRAINT [symbol]] CHECK (expr) [[NOT] ENFORCED]
     */
    private function parseCheck(TokenList $tokenList): CheckDefinition
    {
        $tokenList->expect(TokenType::LEFT_PARENTHESIS);
        $expression = $this->expressionParser->parseExpression($tokenList);
        $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

        $enforced = null;
        if ($tokenList->hasKeyword(Keyword::ENFORCED)) {
            $enforced = true;
        } elseif ($tokenList->hasKeywords(Keyword::NOT, Keyword::ENFORCED)) {
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
     */
    private function parseConstraint(TokenList $tokenList): ConstraintDefinition
    {
        $name = null;
        if ($tokenList->hasKeyword(Keyword::CONSTRAINT)) {
            $name = $tokenList->getName();
        }

        $keyword = $tokenList->expectAnyKeyword(Keyword::PRIMARY, Keyword::UNIQUE, Keyword::FOREIGN, Keyword::CHECK);
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
     */
    private function parseForeignKey(TokenList $tokenList): ForeignKeyDefinition
    {
        $tokenList->expectKeywords(Keyword::FOREIGN, Keyword::KEY);
        $indexName = $tokenList->getName();

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
     */
    private function parseReference(TokenList $tokenList): ReferenceDefinition
    {
        $tokenList->expectKeyword(Keyword::REFERENCES);
        $table = new QualifiedName(...$tokenList->expectQualifiedName());

        $columns = $this->parseColumnList($tokenList);

        $matchType = null;
        if ($tokenList->hasKeyword(Keyword::MATCH)) {
            /** @var ForeignKeyMatchType $matchType */
            $matchType = $tokenList->expectKeywordEnum(ForeignKeyMatchType::class);
        }

        $onDelete = $onUpdate = null;
        if ($tokenList->hasKeywords(Keyword::ON, Keyword::DELETE)) {
            $onDelete = $tokenList->expectMultiKeywordsEnum(ForeignKeyAction::class);
        }
        if ($tokenList->hasKeywords(Keyword::ON, Keyword::UPDATE)) {
            $onUpdate = $tokenList->expectMultiKeywordsEnum(ForeignKeyAction::class);
        }
        // order of DELETE/UPDATE may be switched
        if ($onDelete === null && $tokenList->hasKeywords(Keyword::ON, Keyword::DELETE)) {
            $onDelete = $tokenList->expectMultiKeywordsEnum(ForeignKeyAction::class);
        }

        return new ReferenceDefinition($table, $columns, $onDelete, $onUpdate, $matchType);
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
     * @return mixed[] (string|null $name, mixed $value)
     */
    private function parseTableOption(TokenList $tokenList): array
    {
        $position = $tokenList->getPosition();
        $token = $tokenList->get(TokenType::KEYWORD);
        if ($token === null) {
            return [null, null];
        }

        $keyword = $token->value;
        switch ($keyword) {
            case Keyword::AUTO_INCREMENT:
                $tokenList->passEqual();

                return [TableOption::AUTO_INCREMENT, $tokenList->expectInt()];
            case Keyword::AVG_ROW_LENGTH:
                $tokenList->passEqual();

                return [TableOption::AVG_ROW_LENGTH, $tokenList->expectInt()];
            case Keyword::CHARACTER:
                $tokenList->expectKeyword(Keyword::SET);
                // fall-through
            case Keyword::CHARSET:
                $tokenList->passEqual();

                return [TableOption::CHARACTER_SET, $tokenList->expectCharsetName()];
            case Keyword::CHECKSUM:
                $tokenList->passEqual();

                return [TableOption::CHECKSUM, $tokenList->expectBool()];
            case Keyword::COLLATE:
                $tokenList->passEqual();

                return [TableOption::COLLATE, $tokenList->expectCollationName()];
            case Keyword::COMMENT:
                $tokenList->passEqual();

                return [TableOption::COMMENT, $tokenList->expectString()];
            case Keyword::COMPRESSION:
                $tokenList->passEqual();

                return [TableOption::COMPRESSION, TableCompression::get($tokenList->expectString())];
            case Keyword::CONNECTION:
                $tokenList->passEqual();

                return [TableOption::CONNECTION, $tokenList->expectString()];
            case Keyword::DATA:
                $tokenList->expectKeyword(Keyword::DIRECTORY);
                $tokenList->passEqual();

                return [TableOption::DATA_DIRECTORY, $tokenList->expectString()];
            case Keyword::DEFAULT:
                if ($tokenList->hasKeyword(Keyword::CHARSET)) {
                    $tokenList->passEqual();

                    return [TableOption::CHARACTER_SET, $tokenList->expectCharsetName()];
                } elseif ($tokenList->hasKeyword(Keyword::CHARACTER)) {
                    $tokenList->expectKeyword(Keyword::SET);
                    $tokenList->passEqual();

                    return [TableOption::CHARACTER_SET, $tokenList->expectCharsetName()];
                } else {
                    $tokenList->expectKeyword(Keyword::COLLATE);
                    $tokenList->passEqual();

                    return [TableOption::COLLATE, $tokenList->expectCollationName()];
                }
            case Keyword::DELAY_KEY_WRITE:
                $tokenList->passEqual();

                return [TableOption::DELAY_KEY_WRITE, $tokenList->expectBool()];
            case Keyword::ENCRYPTION:
                $tokenList->passEqual();

                return [TableOption::ENCRYPTION, $tokenList->expectBool()];
            case Keyword::ENGINE:
                $tokenList->passEqual();

                return [TableOption::ENGINE, $tokenList->expectNameOrStringEnum(StorageEngine::class)];
            case Keyword::INDEX:
                $tokenList->expectKeyword(Keyword::DIRECTORY);
                $tokenList->passEqual();

                return [TableOption::INDEX_DIRECTORY, $tokenList->expectString()];
            case Keyword::INSERT_METHOD:
                $tokenList->passEqual();

                return [TableOption::INSERT_METHOD, $tokenList->expectKeywordEnum(TableInsertMethod::class)];
            case Keyword::KEY_BLOCK_SIZE:
                $tokenList->passEqual();

                return [TableOption::KEY_BLOCK_SIZE, $tokenList->expectInt()];
            case Keyword::MAX_ROWS:
                $tokenList->passEqual();

                return [TableOption::MAX_ROWS, $tokenList->expectInt()];
            case Keyword::MIN_ROWS:
                $tokenList->passEqual();

                return [TableOption::MIN_ROWS, $tokenList->expectInt()];
            case Keyword::PACK_KEYS:
                $tokenList->passEqual();
                if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
                    return [TableOption::PACK_KEYS, ThreeStateValue::get(ThreeStateValue::DEFAULT)];
                } else {
                    return [TableOption::PACK_KEYS, ThreeStateValue::get((string) $tokenList->expectInt())];
                }
            case Keyword::PASSWORD:
                $tokenList->passEqual();

                return [TableOption::PASSWORD, $tokenList->expectString()];
            case Keyword::ROW_FORMAT:
                $tokenList->passEqual();

                return [TableOption::ROW_FORMAT, $tokenList->expectKeywordEnum(TableRowFormat::class)];
            case Keyword::STATS_AUTO_RECALC:
                $tokenList->passEqual();
                if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
                    return [TableOption::STATS_AUTO_RECALC, ThreeStateValue::get(ThreeStateValue::DEFAULT)];
                } else {
                    return [TableOption::STATS_AUTO_RECALC, ThreeStateValue::get((string) $tokenList->expectInt())];
                }
            case Keyword::STATS_PERSISTENT:
                $tokenList->passEqual();
                if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
                    return [TableOption::STATS_PERSISTENT, ThreeStateValue::get(ThreeStateValue::DEFAULT)];
                } else {
                    return [TableOption::STATS_PERSISTENT, ThreeStateValue::get((string) $tokenList->expectInt())];
                }
            case Keyword::STATS_SAMPLE_PAGES:
                $tokenList->passEqual();

                return [TableOption::STATS_SAMPLE_PAGES, $tokenList->expectInt()];
            case Keyword::TABLESPACE:
                $tokenList->passEqual();

                return [TableOption::TABLESPACE, $tokenList->expectNameOrString()];
            case Keyword::UNION:
                $tokenList->passEqual();
                $tokenList->expect(TokenType::LEFT_PARENTHESIS);
                $tables = [];
                do {
                    $tables[] = new QualifiedName(...$tokenList->expectQualifiedName());
                } while ($tokenList->hasComma());
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

                return [TableOption::UNION, $tables];
            default:
                $tokenList->resetPosition($position);

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
     */
    private function parsePartitioning(TokenList $tokenList): PartitioningDefinition
    {
        $tokenList->expectKeywords(Keyword::PARTITION, Keyword::BY);
        $condition = $this->parsePartitionCondition($tokenList);

        $partitionsNumber = null;
        if ($tokenList->hasKeyword(Keyword::PARTITIONS)) {
            $partitionsNumber = $tokenList->expectInt();
        }
        $subpartitionsCondition = $subpartitionsNumber = null;
        if ($tokenList->hasKeywords(Keyword::SUBPARTITION, Keyword::BY)) {
            $subpartitionsCondition = $this->parsePartitionCondition($tokenList, true);
            if ($tokenList->hasKeyword(Keyword::SUBPARTITIONS)) {
                $subpartitionsNumber = $tokenList->expectInt();
            }
        }
        $partitions = null;
        if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
            $partitions = [];
            do {
                $partitions[] = $this->parsePartitionDefinition($tokenList);
            } while ($tokenList->hasComma());
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
        }

        return new PartitioningDefinition($condition, $partitions, $partitionsNumber, $subpartitionsCondition, $subpartitionsNumber);
    }

    /**
     * condition:
     *     [LINEAR] HASH(expr)
     *   | [LINEAR] KEY [ALGORITHM={1|2}] (column_list)
     *   | RANGE{(expr) | COLUMNS(column_list)}
     *   | LIST{(expr) | COLUMNS(column_list)}
     */
    private function parsePartitionCondition(TokenList $tokenList, bool $subpartition = false): PartitioningCondition
    {
        $linear = $tokenList->hasKeyword(Keyword::LINEAR);
        if ($linear || $subpartition) {
            $keywords = [Keyword::HASH, Keyword::KEY];
        } else {
            $keywords = [Keyword::HASH, Keyword::KEY, Keyword::RANGE, Keyword::LIST];
        }
        $keyword = $tokenList->expectAnyKeyword(...$keywords);
        if ($keyword === Keyword::HASH) {
            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            $expression = $this->expressionParser->parseExpression($tokenList);
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            $type = PartitioningConditionType::get($linear ? PartitioningConditionType::LINEAR_HASH : PartitioningConditionType::HASH);
            $condition = new PartitioningCondition($type, $expression);
        } elseif ($keyword === Keyword::KEY) {
            $algorithm = null;
            if ($tokenList->hasKeyword(Keyword::ALGORITHM)) {
                $tokenList->expectOperator(Operator::EQUAL);
                $algorithm = $tokenList->expectInt();
            }
            $columns = $this->parseColumnList($tokenList, true);
            $type = PartitioningConditionType::get($linear ? PartitioningConditionType::LINEAR_KEY : PartitioningConditionType::KEY);
            $condition = new PartitioningCondition($type, null, $columns, $algorithm);
        } elseif ($keyword === Keyword::RANGE) {
            $type = PartitioningConditionType::get(PartitioningConditionType::RANGE);
            if ($tokenList->hasKeyword(Keyword::COLUMNS)) {
                $columns = $this->parseColumnList($tokenList);
                $condition = new PartitioningCondition($type, null, $columns);
            } else {
                $tokenList->get(TokenType::LEFT_PARENTHESIS);
                $expression = $this->expressionParser->parseExpression($tokenList);
                $tokenList->get(TokenType::RIGHT_PARENTHESIS);
                $condition = new PartitioningCondition($type, $expression);
            }
        } else {
            $type = PartitioningConditionType::get(PartitioningConditionType::LIST);
            if ($tokenList->hasKeyword(Keyword::COLUMNS)) {
                $columns = $this->parseColumnList($tokenList);
                $condition = new PartitioningCondition($type, null, $columns);
            } else {
                $tokenList->get(TokenType::LEFT_PARENTHESIS);
                $expression = $this->expressionParser->parseExpression($tokenList);
                $tokenList->get(TokenType::RIGHT_PARENTHESIS);
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
     */
    private function parsePartitionDefinition(TokenList $tokenList): PartitionDefinition
    {
        $tokenList->expectKeyword(Keyword::PARTITION);
        $name = $tokenList->expectName();

        $lessThan = $values = null;
        if ($tokenList->hasKeyword(Keyword::VALUES)) {
            if ($tokenList->hasKeywords(Keyword::LESS, Keyword::THAN)) {
                if ($tokenList->hasKeyword(Keyword::MAXVALUE)) {
                    $lessThan = PartitionDefinition::MAX_VALUE;
                } else {
                    $tokenList->expect(TokenType::LEFT_PARENTHESIS);
                    if ($tokenList->seek(TokenType::COMMA, 2) !== null) {
                        $lessThan = [];
                        do {
                            $lessThan[] = $this->expressionParser->parseLiteralValue($tokenList);
                            if (!$tokenList->has(TokenType::COMMA)) {
                                break;
                            }
                        } while (true);
                    } else {
                        $lessThan = $this->expressionParser->parseExpression($tokenList);
                    }
                    $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
                }
            } else {
                $tokenList->expectKeyword(Keyword::IN);
                $tokenList->expect(TokenType::LEFT_PARENTHESIS);
                $values = [];
                do {
                    $values[] = $this->expressionParser->parseLiteralValue($tokenList);
                } while ($tokenList->hasComma());
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            }
        }

        $options = $this->parsePartitionOptions($tokenList);

        $subpartitions = null;
        if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
            $subpartitions = [];
            do {
                $tokenList->expectKeyword(Keyword::SUBPARTITION);
                $subName = $tokenList->expectName();
                $subOptions = $this->parsePartitionOptions($tokenList);
                $subpartitions[$subName] = $subOptions;
            } while ($tokenList->hasComma());
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
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
     * @return mixed[]
     */
    private function parsePartitionOptions(TokenList $tokenList): ?array
    {
        $options = [];

        if ($tokenList->hasKeyword(Keyword::STORAGE)) {
            $tokenList->expectKeyword(Keyword::ENGINE);
            $tokenList->passEqual();
            $options[PartitionOption::ENGINE] = $tokenList->expectNameOrString();
        } elseif ($tokenList->hasKeyword(Keyword::ENGINE)) {
            $tokenList->passEqual();
            $options[PartitionOption::ENGINE] = $tokenList->expectNameOrString();
        }
        if ($tokenList->hasKeyword(Keyword::COMMENT)) {
            $tokenList->passEqual();
            $options[PartitionOption::COMMENT] = $tokenList->expectString();
        }
        if ($tokenList->hasKeywords(Keyword::DATA, Keyword::DIRECTORY)) {
            $tokenList->passEqual();
            $options[PartitionOption::DATA_DIRECTORY] = $tokenList->expectString();
        }
        if ($tokenList->hasKeywords(Keyword::INDEX, Keyword::DIRECTORY)) {
            $tokenList->passEqual();
            $options[PartitionOption::INDEX_DIRECTORY] = $tokenList->expectString();
        }
        if ($tokenList->hasKeyword(Keyword::MAX_ROWS)) {
            $tokenList->passEqual();
            $options[PartitionOption::MAX_ROWS] = $tokenList->expectInt();
        }
        if ($tokenList->hasKeyword(Keyword::MIN_ROWS)) {
            $tokenList->passEqual();
            $options[PartitionOption::MIN_ROWS] = $tokenList->expectInt();
        }
        if ($tokenList->hasKeyword(Keyword::TABLESPACE)) {
            $tokenList->passEqual();
            $options[PartitionOption::TABLESPACE] = $tokenList->expectString();
        }

        return $options !== [] ? $options : null;
    }

    /**
     * @return string[]|null
     */
    private function parsePartitionNames(TokenList $tokenList): ?array
    {
        if ($tokenList->hasKeyword(Keyword::ALL)) {
            return null;
        }
        $names = [];
        do {
            $names[] = $tokenList->expectName();
        } while ($tokenList->hasComma());

        return $names;
    }

    /**
     * @return string[]
     */
    private function parseColumnList(TokenList $tokenList, bool $allowEmpty = false): array
    {
        $columns = [];
        $tokenList->expect(TokenType::LEFT_PARENTHESIS);
        if ($allowEmpty && $tokenList->has(TokenType::RIGHT_PARENTHESIS)) {
            return $columns;
        }

        do {
            $columns[] = $tokenList->expectName();
        } while ($tokenList->hasComma());
        $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

        return $columns;
    }

    /**
     * DROP [TEMPORARY] TABLE [IF EXISTS]
     *     tbl_name [, tbl_name] ...
     *     [RESTRICT | CASCADE]
     */
    public function parseDropTable(TokenList $tokenList): DropTableCommand
    {
        $tokenList->expectKeyword(Keyword::DROP);
        $temporary = $tokenList->hasKeyword(Keyword::TEMPORARY);
        $tokenList->expectAnyKeyword(Keyword::TABLE, Keyword::TABLES);
        $ifExists = $tokenList->hasKeyword(Keyword::IF);
        if ($ifExists) {
            $tokenList->expectKeyword(Keyword::EXISTS);
        }
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->expectQualifiedName());
        } while ($tokenList->hasComma());

        // ignored in MySQL 5.7, 8.0
        $cascadeRestrict = $tokenList->getAnyKeyword(Keyword::CASCADE, Keyword::RESTRICT);
        $cascadeRestrict = $cascadeRestrict === Keyword::CASCADE ? true : ($cascadeRestrict === Keyword::RESTRICT ? false : null);

        return new DropTableCommand($tables, $temporary, $ifExists, $cascadeRestrict);
    }

    /**
     * RENAME TABLE tbl_name TO new_tbl_name
     *     [, tbl_name2 TO new_tbl_name2] ...
     */
    public function parseRenameTable(TokenList $tokenList): RenameTableCommand
    {
        $tokenList->expectKeywords(Keyword::RENAME, Keyword::TABLE);

        $tables = [];
        $newTables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->expectQualifiedName());
            $tokenList->expectKeyword(Keyword::TO);
            $newTables[] = new QualifiedName(...$tokenList->expectQualifiedName());
        } while ($tokenList->hasComma());

        return new RenameTableCommand($tables, $newTables);
    }

    /**
     * TRUNCATE [TABLE] tbl_name
     */
    public function parseTruncateTable(TokenList $tokenList): TruncateTableCommand
    {
        $tokenList->expectKeyword(Keyword::TRUNCATE);
        $tokenList->passKeyword(Keyword::TABLE);
        $table = new QualifiedName(...$tokenList->expectQualifiedName());

        return new TruncateTableCommand($table);
    }

}
