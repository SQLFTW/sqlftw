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
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\KeywordLiteral;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class InsertCommandParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var QueryParser */
    private $queryParser;

    public function __construct(
        ExpressionParser $expressionParser,
        QueryParser $queryParser
    ) {
        $this->expressionParser = $expressionParser;
        $this->queryParser = $queryParser;
    }

    /**
     * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name, ...)]
     *     [(col_name, ...)]
     *     {VALUES | VALUE} ({expr | DEFAULT}, ...), (...), ...
     *     [ ON DUPLICATE KEY UPDATE
     *       col_name=expr [, col_name=expr] ... ]
     *
     * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name, ...)]
     *     SET col_name={expr | DEFAULT}, ...
     *     [ ON DUPLICATE KEY UPDATE
     *       col_name=expr [, col_name=expr] ... ]
     *
     * INSERT [LOW_PRIORITY | HIGH_PRIORITY] [IGNORE]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name, ...)]
     *     [(col_name, ...)]
     *     SELECT ...
     *     [ ON DUPLICATE KEY UPDATE
     *       col_name=expr [, col_name=expr] ... ]
     */
    public function parseInsert(TokenList $tokenList): InsertCommand
    {
        $tokenList->expectKeyword(Keyword::INSERT);
        /** @var InsertPriority|null $priority */
        $priority = $tokenList->getKeywordEnum(InsertPriority::class);
        $ignore = $tokenList->hasKeyword(Keyword::IGNORE);
        $tokenList->passKeyword(Keyword::INTO);
        $table = new QualifiedName(...$tokenList->expectQualifiedName());

        $partitions = $this->parsePartitionsList($tokenList);
        $columns = $this->parseColumnList($tokenList);

        if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
            $tokenList->expectAnyKeyword(Keyword::SELECT, Keyword::WITH, Keyword::TABLE, Keyword::VALUES);
            $query = $this->queryParser->parseQuery($tokenList->resetPosition(-1));
            $update = $this->parseOnDuplicateKeyUpdate($tokenList);
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

            return new InsertSelectCommand($table, $query, $columns, $partitions, $priority, $ignore, $update);
        } elseif ($tokenList->hasAnyKeyword(Keyword::SELECT, Keyword::WITH, Keyword::TABLE)) { // no Keyword::VALUES!
            $query = $this->queryParser->parseQuery($tokenList->resetPosition(-1));
            $update = $this->parseOnDuplicateKeyUpdate($tokenList);

            return new InsertSelectCommand($table, $query, $columns, $partitions, $priority, $ignore, $update);
        } elseif ($tokenList->hasKeyword(Keyword::SET)) {
            $values = $this->parseAssignments($tokenList);
            $update = $this->parseOnDuplicateKeyUpdate($tokenList);

            return new InsertSetCommand($table, $values, $columns, $partitions, $priority, $ignore, $update);
        } else {
            $tokenList->expectAnyKeyword(Keyword::VALUE, Keyword::VALUES);
            $rows = $this->parseRows($tokenList);
            $update = $this->parseOnDuplicateKeyUpdate($tokenList);

            return new InsertValuesCommand($table, $rows, $columns, $partitions, $priority, $ignore, $update);
        }
    }

    /**
     * REPLACE [LOW_PRIORITY | DELAYED]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name, ...)]
     *     [(col_name, ...)]
     *     {VALUES | VALUE} ({expr | DEFAULT}, ...), (...), ...
     *
     * REPLACE [LOW_PRIORITY | DELAYED]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name, ...)]
     *     SET col_name={expr | DEFAULT}, ...
     *
     * REPLACE [LOW_PRIORITY | DELAYED]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name, ...)]
     *     [(col_name, ...)]
     *     SELECT ...
     */
    public function parseReplace(TokenList $tokenList): ReplaceCommand
    {
        $tokenList->expectKeyword(Keyword::REPLACE);
        /** @var InsertPriority|null $priority */
        $priority = $tokenList->getKeywordEnum(InsertPriority::class);
        $ignore = $tokenList->hasKeyword(Keyword::IGNORE);
        $tokenList->passKeyword(Keyword::INTO);
        $table = new QualifiedName(...$tokenList->expectQualifiedName());

        $partitions = $this->parsePartitionsList($tokenList);
        $columns = $this->parseColumnList($tokenList);

        if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
            $tokenList->expectAnyKeyword(Keyword::SELECT, Keyword::WITH, Keyword::TABLE, Keyword::VALUES);
            $query = $this->queryParser->parseQuery($tokenList->resetPosition(-1));
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

            return new ReplaceSelectCommand($table, $query, $columns, $partitions, $priority, $ignore);
        } elseif ($tokenList->hasAnyKeyword(Keyword::SELECT, Keyword::WITH, Keyword::TABLE)) { // no Keyword::VALUES!
            $query = $this->queryParser->parseQuery($tokenList->resetPosition(-1));

            return new ReplaceSelectCommand($table, $query, $columns, $partitions, $priority, $ignore);
        } elseif ($tokenList->hasKeyword(Keyword::SET)) {
            $values = $this->parseAssignments($tokenList);

            return new ReplaceSetCommand($table, $values, $columns, $partitions, $priority, $ignore);
        } else {
            $tokenList->expectAnyKeyword(Keyword::VALUE, Keyword::VALUES);
            $rows = $this->parseRows($tokenList);

            return new ReplaceValuesCommand($table, $rows, $columns, $partitions, $priority, $ignore);
        }
    }

    /**
     * @return string[]|null
     */
    private function parsePartitionsList(TokenList $tokenList): ?array
    {
        $partitions = null;
        if ($tokenList->hasKeyword(Keyword::PARTITION)) {
            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            $partitions = [];
            do {
                $partitions[] = $tokenList->expectName();
            } while ($tokenList->hasComma());
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
        }

        return $partitions;
    }

    /**
     * @return string[]|null
     */
    private function parseColumnList(TokenList $tokenList): ?array
    {
        $position = $tokenList->getPosition();
        $columns = null;
        if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
            if ($tokenList->hasAnyKeyword(Keyword::SELECT, Keyword::TABLE, Keyword::VALUES, Keyword::WITH)) {
                // this is not a column list
                $tokenList->resetPosition($position);
                return $columns;
            }
            $columns = [];
            do {
                $columns[] = $tokenList->expectName();
            } while ($tokenList->hasComma());
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
        }

        return $columns;
    }

    private function parseOnDuplicateKeyUpdate(TokenList $tokenList): ?OnDuplicateKeyActions
    {
        if (!$tokenList->hasKeywords(Keyword::ON, Keyword::DUPLICATE, Keyword::KEY, Keyword::UPDATE)) {
            return null;
        }

        $values = $this->parseAssignments($tokenList);

        return new OnDuplicateKeyActions($values);
    }

    /**
     * @return ExpressionNode[]
     */
    private function parseAssignments(TokenList $tokenList): array
    {
        $values = [];
        do {
            $column = $tokenList->expectName();
            $tokenList->expectOperator(Operator::EQUAL);
            if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
                $values[$column] = new KeywordLiteral(Keyword::DEFAULT);
            } else {
                $values[$column] = $this->expressionParser->parseExpression($tokenList);
            }
        } while ($tokenList->hasComma());

        return $values;
    }

    /**
     * @return ExpressionNode[][]
     */
    private function parseRows(TokenList $tokenList): array
    {
        $rows = [];
        do {
            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            $values = [];
            do {
                if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
                    $values[] = new KeywordLiteral(Keyword::DEFAULT);
                } else {
                    $values[] = $this->expressionParser->parseExpression($tokenList);
                }
            } while ($tokenList->hasComma());
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

            $rows[] = $values;
        } while ($tokenList->hasComma());

        return $rows;
    }

}
