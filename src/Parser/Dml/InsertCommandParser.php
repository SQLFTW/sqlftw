<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dml\Insert\InsertCommand;
use SqlFtw\Sql\Dml\Insert\InsertPriority;
use SqlFtw\Sql\Dml\Insert\InsertSelectCommand;
use SqlFtw\Sql\Dml\Insert\InsertSetCommand;
use SqlFtw\Sql\Dml\Insert\InsertValuesCommand;
use SqlFtw\Sql\Dml\Insert\OnDuplicateKeyActions;
use SqlFtw\Sql\Dml\Insert\ReplaceCommand;
use SqlFtw\Sql\Dml\Insert\ReplaceSelectCommand;
use SqlFtw\Sql\Dml\Insert\ReplaceSetCommand;
use SqlFtw\Sql\Dml\Insert\ReplaceValuesCommand;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class InsertCommandParser
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\ExpressionParser */
    private $expressionParser;

    /** @var \SqlFtw\Parser\Dml\SelectCommandParser */
    private $selectCommandParser;

    public function __construct(
        ExpressionParser $expressionParser,
        SelectCommandParser $selectCommandParser
    ) {
        $this->expressionParser = $expressionParser;
        $this->selectCommandParser = $selectCommandParser;
    }

    /**
     * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name,...)]
     *     [(col_name,...)]
     *     {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
     *     [ ON DUPLICATE KEY UPDATE
     *       col_name=expr [, col_name=expr] ... ]
     *
     * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name,...)]
     *     SET col_name={expr | DEFAULT}, ...
     *     [ ON DUPLICATE KEY UPDATE
     *       col_name=expr [, col_name=expr] ... ]
     *
     * INSERT [LOW_PRIORITY | HIGH_PRIORITY] [IGNORE]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name,...)]
     *     [(col_name,...)]
     *     SELECT ...
     *     [ ON DUPLICATE KEY UPDATE
     *       col_name=expr [, col_name=expr] ... ]
     */
    public function parseInsert(TokenList $tokenList): InsertCommand
    {
        $tokenList->consumeKeyword(Keyword::INSERT);
        /** @var \SqlFtw\Sql\Dml\Insert\InsertPriority|null $priority */
        $priority = $tokenList->mayConsumeKeywordEnum(InsertPriority::class);
        $ignore = (bool) $tokenList->mayConsumeKeyword(Keyword::IGNORE);
        $tokenList->mayConsumeKeyword(Keyword::INTO);
        $table = new QualifiedName(...$tokenList->consumeQualifiedName());

        $partitions = $this->parsePartitionsList($tokenList);
        $columns = $this->parseColumnList($tokenList);

        if ($tokenList->mayConsumeKeyword(Keyword::SELECT)) {
            $select = $this->selectCommandParser->parseSelect($tokenList->resetPosition(-1));
            $update = $this->parseOnDuplicateKeyUpdate($tokenList);

            return new InsertSelectCommand($table, $select, $columns, $partitions, $priority, $ignore, $update);
        } elseif ($tokenList->mayConsumeKeyword(Keyword::SET)) {
            $values = $this->parseAssignments($tokenList);
            $update = $this->parseOnDuplicateKeyUpdate($tokenList);

            return new InsertSetCommand($table, $values, $columns, $partitions, $priority, $ignore, $update);
        } else {
            $tokenList->consumeAnyKeyword(Keyword::VALUE, Keyword::VALUES);
            $rows = $this->parseRows($tokenList);
            $update = $this->parseOnDuplicateKeyUpdate($tokenList);

            return new InsertValuesCommand($table, $rows, $columns, $partitions, $priority, $ignore, $update);
        }
    }

    /**
     * REPLACE [LOW_PRIORITY | DELAYED]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name,...)]
     *     [(col_name,...)]
     *     {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
     *
     * REPLACE [LOW_PRIORITY | DELAYED]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name,...)]
     *     SET col_name={expr | DEFAULT}, ...
     *
     * REPLACE [LOW_PRIORITY | DELAYED]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name,...)]
     *     [(col_name,...)]
     *     SELECT ...
     */
    public function parseReplace(TokenList $tokenList): ReplaceCommand
    {
        $tokenList->consumeKeyword(Keyword::REPLACE);
        /** @var \SqlFtw\Sql\Dml\Insert\InsertPriority|null $priority */
        $priority = $tokenList->mayConsumeKeywordEnum(InsertPriority::class);
        $ignore = (bool) $tokenList->mayConsumeKeyword(Keyword::IGNORE);
        $tokenList->mayConsumeKeyword(Keyword::INTO);
        $table = new QualifiedName(...$tokenList->consumeQualifiedName());

        $partitions = $this->parsePartitionsList($tokenList);
        $columns = $this->parseColumnList($tokenList);

        if ($tokenList->mayConsumeKeyword(Keyword::SELECT)) {
            $select = $this->selectCommandParser->parseSelect($tokenList->resetPosition(-1));

            return new ReplaceSelectCommand($table, $select, $columns, $partitions, $priority, $ignore);
        } elseif ($tokenList->mayConsumeKeyword(Keyword::SET)) {
            $values = $this->parseAssignments($tokenList);

            return new ReplaceSetCommand($table, $values, $columns, $partitions, $priority, $ignore);
        } else {
            $tokenList->consumeAnyKeyword(Keyword::VALUE, Keyword::VALUES);
            $rows = $this->parseRows($tokenList);

            return new ReplaceValuesCommand($table, $rows, $columns, $partitions, $priority, $ignore);
        }
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return string[]|null
     */
    private function parsePartitionsList(TokenList $tokenList): ?array
    {
        $partitions = null;
        if ($tokenList->mayConsumeKeyword(Keyword::PARTITION)) {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $partitions = [];
            do {
                $partitions[] = $tokenList->consumeName();
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }

        return $partitions;
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return string[]|null
     */
    private function parseColumnList(TokenList $tokenList): ?array
    {
        $columns = null;
        if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
            $columns = [];
            do {
                $columns[] = $tokenList->consumeName();
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }

        return $columns;
    }

    private function parseOnDuplicateKeyUpdate(TokenList $tokenList): ?OnDuplicateKeyActions
    {
        if (!$tokenList->mayConsumeKeywords(Keyword::ON, Keyword::DUPLICATE, Keyword::KEY, Keyword::UPDATE)) {
            return null;
        }

        $values = $this->parseAssignments($tokenList);

        return new OnDuplicateKeyActions($values);
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Expression\ExpressionNode[]
     */
    private function parseAssignments(TokenList $tokenList): array
    {
        $values = [];
        do {
            $column = $tokenList->consumeName();
            $tokenList->consumeOperator(Operator::EQUAL);
            $value = $this->expressionParser->parseExpression($tokenList);
            $values[$column] = $value;
        } while ($tokenList->mayConsumeComma());

        return $values;
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Expression\ExpressionNode[][]
     */
    private function parseRows(TokenList $tokenList): array
    {
        $rows = [];
        do {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $values = [];
            do {
                $values[] = $this->expressionParser->parseExpression($tokenList);
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

            $rows[] = $values;
        } while ($tokenList->mayConsumeComma());

        return $rows;
    }

}
