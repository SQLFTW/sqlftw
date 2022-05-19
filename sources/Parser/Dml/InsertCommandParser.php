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
use SqlFtw\Sql\Dml\Assignment;
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
        $table = $tokenList->expectQualifiedName();

        $partitions = $this->parsePartitionsList($tokenList);
        $columns = $this->parseColumnList($tokenList);

        if ($tokenList->hasAnyKeyword(Keyword::VALUE, Keyword::VALUES)) {
            $rows = $this->parseRows($tokenList);
            $update = $this->parseOnDuplicateKeyUpdate($tokenList);

            return new InsertValuesCommand($table, $rows, $columns, $partitions, $priority, $ignore, $update);
        } elseif ($tokenList->hasKeyword(Keyword::SET)) {
            $assignments = $this->parseAssignments($tokenList);
            $update = $this->parseOnDuplicateKeyUpdate($tokenList);

            return new InsertSetCommand($table, $assignments, $columns, $partitions, $priority, $ignore, $update);
        } else {
            $query = $this->queryParser->parseQuery($tokenList);
            $update = $this->parseOnDuplicateKeyUpdate($tokenList);

            return new InsertSelectCommand($table, $query, $columns, $partitions, $priority, $ignore, $update);
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
        $table = $tokenList->expectQualifiedName();

        $partitions = $this->parsePartitionsList($tokenList);
        $columns = $this->parseColumnList($tokenList);

        if ($tokenList->hasSymbol('(')) {
            $tokenList->expectAnyKeyword(Keyword::SELECT, Keyword::WITH, Keyword::TABLE, Keyword::VALUES);
            $query = $this->queryParser->parseQuery($tokenList->resetPosition(-1));
            $tokenList->expectSymbol(')');

            return new ReplaceSelectCommand($table, $query, $columns, $partitions, $priority, $ignore);
        } elseif ($tokenList->hasAnyKeyword(Keyword::SELECT, Keyword::WITH, Keyword::TABLE)) { // no Keyword::VALUES!
            $query = $this->queryParser->parseQuery($tokenList->resetPosition(-1));

            return new ReplaceSelectCommand($table, $query, $columns, $partitions, $priority, $ignore);
        } elseif ($tokenList->hasKeyword(Keyword::SET)) {
            $assignments = $this->parseAssignments($tokenList);

            return new ReplaceSetCommand($table, $assignments, $columns, $partitions, $priority, $ignore);
        } else {
            $tokenList->expectAnyKeyword(Keyword::VALUE, Keyword::VALUES);
            $rows = $this->parseRows($tokenList);

            return new ReplaceValuesCommand($table, $rows, $columns, $partitions, $priority, $ignore);
        }
    }

    /**
     * @return non-empty-array<string>|null
     */
    private function parsePartitionsList(TokenList $tokenList): ?array
    {
        $partitions = null;
        if ($tokenList->hasKeyword(Keyword::PARTITION)) {
            $tokenList->expectSymbol('(');
            $partitions = [];
            do {
                $partitions[] = $tokenList->expectName();
            } while ($tokenList->hasSymbol(','));
            $tokenList->expectSymbol(')');
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
        if ($tokenList->hasSymbol('(')) {
            if ($tokenList->hasSymbol(')')) {
                return [];
            }
            if ($tokenList->hasAnyKeyword(Keyword::SELECT, Keyword::TABLE, Keyword::VALUES, Keyword::WITH)) {
                // this is not a column list
                $tokenList->resetPosition($position);
                return null;
            }
            $columns = [];
            do {
                $columns[] = $tokenList->expectName();
            } while ($tokenList->hasSymbol(','));
            $tokenList->expectSymbol(')');
        }

        return $columns;
    }

    private function parseOnDuplicateKeyUpdate(TokenList $tokenList): ?OnDuplicateKeyActions
    {
        if (!$tokenList->hasKeywords(Keyword::ON, Keyword::DUPLICATE, Keyword::KEY, Keyword::UPDATE)) {
            return null;
        }

        $assignments = $this->parseAssignments($tokenList);

        return new OnDuplicateKeyActions($assignments);
    }

    /**
     * @return non-empty-array<Assignment>
     */
    private function parseAssignments(TokenList $tokenList): array
    {
        $assignments = [];
        do {
            $column = $tokenList->expectQualifiedName();
            $tokenList->expectOperator(Operator::EQUAL);
            if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
                $assignments[] = new Assignment($column, new KeywordLiteral(Keyword::DEFAULT));
            } else {
                $assignments[] = new Assignment($column, $this->expressionParser->parseExpression($tokenList));
            }
        } while ($tokenList->hasSymbol(','));

        return $assignments;
    }

    /**
     * @return non-empty-array<array<ExpressionNode>>
     */
    private function parseRows(TokenList $tokenList): array
    {
        $rows = [];
        do {
            $tokenList->expectSymbol('(');

            $values = [];
            if (!$tokenList->hasSymbol(')')) {
                do {
                    if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
                        $values[] = new KeywordLiteral(Keyword::DEFAULT);
                    } else {
                        $values[] = $this->expressionParser->parseAssignExpression($tokenList);
                    }
                } while ($tokenList->hasSymbol(','));
                $tokenList->expectSymbol(')');
            }

            $rows[] = $values;
        } while ($tokenList->hasSymbol(','));

        return $rows;
    }

}
