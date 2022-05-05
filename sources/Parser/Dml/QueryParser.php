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
use SqlFtw\Parser\JoinParser;
use SqlFtw\Parser\ParserException;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Dml\Query\GroupByExpression;
use SqlFtw\Sql\Dml\Query\ParenthesizedQueryExpression;
use SqlFtw\Sql\Dml\Query\Query;
use SqlFtw\Sql\Dml\Query\Row;
use SqlFtw\Sql\Dml\Query\SelectCommand;
use SqlFtw\Sql\Dml\Query\SelectDistinctOption;
use SqlFtw\Sql\Dml\Query\SelectExpression;
use SqlFtw\Sql\Dml\Query\SelectInto;
use SqlFtw\Sql\Dml\Query\SelectLocking;
use SqlFtw\Sql\Dml\Query\SelectLockOption;
use SqlFtw\Sql\Dml\Query\SelectLockWaitOption;
use SqlFtw\Sql\Dml\Query\SelectOption;
use SqlFtw\Sql\Dml\Query\SimpleQuery;
use SqlFtw\Sql\Dml\Query\TableCommand;
use SqlFtw\Sql\Dml\Query\UnionExpression;
use SqlFtw\Sql\Dml\Query\UnionType;
use SqlFtw\Sql\Dml\Query\ValuesCommand;
use SqlFtw\Sql\Dml\Query\WindowFrame;
use SqlFtw\Sql\Dml\Query\WindowFrameType;
use SqlFtw\Sql\Dml\Query\WindowFrameUnits;
use SqlFtw\Sql\Dml\Query\WindowSpecification;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Order;
use SqlFtw\Sql\QualifiedName;
use function array_pop;
use function preg_match;

class QueryParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var FileFormatParser */
    private $fileFormatParser;

    /** @var JoinParser */
    private $joinParser;

    /** @var WithParser */
    private $withParser;

    public function __construct(
        ExpressionParser $expressionParser,
        FileFormatParser $fileFormatParser,
        JoinParser $joinParser,
        WithParser $withParser
    ) {
        $this->expressionParser = $expressionParser;
        $this->fileFormatParser = $fileFormatParser;
        $this->joinParser = $joinParser;
        $this->withParser = $withParser;
    }

    /**
     * query:
     *     parenthesized_query_expression
     *   | query_expression
     *
     * parenthesized_query_expression:
     *   ( query_expression [order_by_clause] [limit_clause] )
     *     [order_by_clause]
     *     [limit_clause]
     *     [into_clause]
     *
     * query_expression:
     *   query_block [UNION [ALL | DISTINCT] query_block [UNION [ALL | DISTINCT] query_block ...]]
     *     [order_by_clause]
     *     [limit_clause]
     *     [into_clause]
     *
     * query_bock:
     *     parenthesized_query_expression
     *   | [WITH ...] SELECT ...
     *   | TABLE ...
     *   | VALUES ...
     */
    public function parseQuery(TokenList $tokenList): Query
    {
        $queries = [$this->parseQueryBlock($tokenList)];
        $types = [];
        while ($tokenList->hasKeyword(Keyword::UNION)) {
            if ($tokenList->hasKeyword(Keyword::ALL)) {
                $types[] = UnionType::get(UnionType::ALL);
            } else {
                $tokenList->passKeyword(Keyword::DISTINCT);
                $types[] = UnionType::get(UnionType::DISTINCT);
            }
            $queries[] = $this->parseQueryBlock($tokenList);
        }

        if (count($queries) === 1) {
            return $queries[0];
        }

        [$orderBy, $limit, , $into] = $this->parseOrderLimitOffsetInto($tokenList, false);

        // order, limit and into of last unparenthesized query belong to the whole union result
        $lastQuery = array_pop($queries);
        if ($lastQuery instanceof SimpleQuery) {
            $queryOrderBy = $lastQuery->getOrderBy();
            if ($queryOrderBy !== null) {
                if ($orderBy !== null) {
                    throw new ParserException("Duplicate ORDER BY clause in last query and in UNION.");
                } else {
                    $orderBy = $queryOrderBy;
                    $lastQuery = $lastQuery->removeLimit();
                }
            }

            $queryLimit = $lastQuery->getLimit();
            if ($queryLimit !== null) {
                if ($limit !== null) {
                    throw new ParserException("Duplicate LIMIT clause in last query and in UNION.");
                } else {
                    $limit = $queryLimit;
                    $lastQuery = $lastQuery->removeLimit();
                }
            }

            $queryInto = $lastQuery->getInto();
            if ($queryInto !== null) {
                if ($into !== null) {
                    throw new ParserException("Duplicate INTO clause in last query and in UNION.");
                } else {
                    $into = $queryInto;
                    $lastQuery = $lastQuery->removeInto();
                }
            }
        }
        $queries[] = $lastQuery;

        return new UnionExpression($queries, $types, $orderBy, $limit, $into);
    }

    public function parseQueryBlock(TokenList $tokenList): Query
    {
        if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
            return $this->parseParenthesizedQueryExpression($tokenList->resetPosition(-1));
        } elseif ($tokenList->hasAnyKeyword(Keyword::SELECT, Keyword::WITH)) {
            return $this->parseSelect($tokenList->resetPosition(-1));
        } elseif ($tokenList->hasKeyword(Keyword::TABLE)) {
            return $this->parseTable($tokenList->resetPosition(-1));
        } elseif ($tokenList->hasKeyword(Keyword::VALUES)) {
            return $this->parseValues($tokenList->resetPosition(-1));
        } else {
            $tokenList->expectedAnyKeyword(Keyword::SELECT, Keyword::TABLE, Keyword::VALUES, Keyword::WITH);
        }
    }

    private function parseParenthesizedQueryExpression(TokenList $tokenList): ParenthesizedQueryExpression
    {
        $tokenList->expect(TokenType::LEFT_PARENTHESIS);
        $query = $this->parseQuery($tokenList);
        $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

        [$orderBy, $limit, , $into] = $this->parseOrderLimitOffsetInto($tokenList, false);

        return new ParenthesizedQueryExpression($query, $orderBy, $limit, $into);
    }

    /**
     * SELECT
     *     [ALL | DISTINCT | DISTINCTROW ]
     *     [HIGH_PRIORITY]
     *     [STRAIGHT_JOIN]
     *     [SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
     *     [SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
     *     select_expr [, select_expr ...]
     *     [into_option]
     *     [FROM table_references
     *       [PARTITION partition_list]
     *     [WHERE where_condition]
     *     [GROUP BY {col_name | expr | position}
     *       [ASC | DESC], ... [WITH ROLLUP]]
     *     [HAVING where_condition]
     *     [WINDOW window_name AS (window_spec)
     *       [, window_name AS (window_spec)] ...]
     *     [ORDER BY {col_name | expr | position}
     *       [ASC | DESC], ...]
     *     [LIMIT {[offset,] row_count | row_count OFFSET offset}]
     *     [into_option]
     *     [FOR UPDATE | LOCK IN SHARE MODE]]
     *     [FOR {UPDATE | SHARE} [OF tbl_name [, tbl_name] ...] [NOWAIT | SKIP LOCKED]
     *       | LOCK IN SHARE MODE]]
     *     [into_option]
     */
    public function parseSelect(TokenList $tokenList, ?WithClause $with = null): SelectCommand
    {
        if ($tokenList->hasKeyword(Keyword::WITH)) {
            if ($with !== null) {
                throw new ParserException('WITH defined twice.');
            }

            /** @var SelectCommand $select */
            $select = $this->withParser->parseWith($tokenList->resetPosition(-1));

            return $select;
        }

        $tokenList->expectKeyword(Keyword::SELECT);

        /** @var SelectDistinctOption $distinct */
        $distinct = $tokenList->getKeywordEnum(SelectDistinctOption::class);
        $options = [];
        $options[SelectOption::HIGH_PRIORITY] = $tokenList->hasKeyword(Keyword::HIGH_PRIORITY);
        $options[SelectOption::STRAIGHT_JOIN] = $tokenList->hasKeyword(Keyword::STRAIGHT_JOIN);
        $options[SelectOption::SMALL_RESULT] = $tokenList->hasKeyword(Keyword::SQL_SMALL_RESULT);
        $options[SelectOption::BIG_RESULT] = $tokenList->hasKeyword(Keyword::SQL_BIG_RESULT);
        $options[SelectOption::BUFFER_RESULT] = $tokenList->hasKeyword(Keyword::SQL_BUFFER_RESULT);
        $options[SelectOption::CACHE] = $tokenList->hasKeyword(Keyword::SQL_CACHE);
        $options[SelectOption::NO_CACHE] = $tokenList->hasKeyword(Keyword::SQL_NO_CACHE);
        $options[SelectOption::CALC_FOUND_ROWS] = $tokenList->hasKeyword(Keyword::SQL_CALC_FOUND_ROWS);

        $what = [];
        do {
            if ($tokenList->hasOperator(Operator::MULTIPLY)) {
                $expression = new Identifier('*');
            } else {
                $expression = $this->expressionParser->parseAssignExpression($tokenList);
            }
            $window = null;
            if ($tokenList->hasKeyword(Keyword::OVER)) {
                if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
                    $window = $this->parseWindow($tokenList);
                    $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
                } else {
                    $window = $tokenList->expectName();
                }
            }
            if ($tokenList->hasKeyword(Keyword::AS)) {
                $alias = $tokenList->expectName();
            } else {
                $alias = $tokenList->getNonKeywordName();
            }
            $what[] = new SelectExpression($expression, $alias, $window);
        } while ($tokenList->hasComma());

        $into = null;
        if ($tokenList->hasKeyword(Keyword::INTO)) {
            $into = $this->parseInto($tokenList);
        }

        $from = null;
        if ($tokenList->hasKeyword(Keyword::FROM)) {
            $from = $this->joinParser->parseTableReferences($tokenList);
            /*
            // todo: should be part of the table references or not?
            if ($tokenList->mayConsumeKeyword(Keyword::PARTITION)) {
                $partitions = [];
                do {
                    $partitions[] = $tokenList->consumeName();
                } while ($tokenList->mayConsumeComma());
            }
            */
        }

        $where = null;
        if ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        $groupBy = null;
        $withRollup = false;
        if ($tokenList->hasKeywords(Keyword::GROUP, Keyword::BY)) {
            $groupBy = [];
            do {
                $expression = $this->expressionParser->parseExpression($tokenList);
                /** @var Order $order */
                $order = $tokenList->getKeywordEnum(Order::class);
                $groupBy[] = new GroupByExpression($expression, $order);
            } while ($tokenList->hasComma());

            $withRollup = $tokenList->hasKeywords(Keyword::WITH, Keyword::ROLLUP);
        }

        $having = null;
        if ($tokenList->hasKeyword(Keyword::HAVING)) {
            $having = $this->expressionParser->parseExpression($tokenList);
        }

        $windows = null;
        if ($tokenList->hasKeyword(Keyword::WINDOW)) {
            $windows = [];
            do {
                $name = $tokenList->expectName();
                $tokenList->expectKeyword(Keyword::AS);

                $tokenList->expect(TokenType::LEFT_PARENTHESIS);
                $window = $this->parseWindow($tokenList);
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

                $windows[$name] = $window;
            } while ($tokenList->hasComma());
        }

        $orderBy = null;
        if ($tokenList->hasKeywords(Keyword::ORDER, Keyword::BY)) {
            $orderBy = $this->expressionParser->parseOrderBy($tokenList);
        }

        $limit = $offset = null;
        if ($tokenList->hasKeyword(Keyword::LIMIT)) {
            [$limit, $offset] = $this->expressionParser->parseLimitAndOffset($tokenList);
        }

        if ($into === null && $tokenList->hasKeyword(Keyword::INTO)) {
            $into = $this->parseInto($tokenList);
        }

        $locking = $lockTables = null;
        if ($tokenList->hasKeywords(Keyword::LOCK, Keyword::IN, Keyword::SHARE, Keyword::MODE)) {
            $lockOption = SelectLockOption::get(SelectLockOption::LOCK_IN_SHARE_MODE);
            $locking = new SelectLocking($lockOption);
        } elseif ($tokenList->hasKeyword(Keyword::FOR)) {
            if ($tokenList->hasKeyword(Keyword::UPDATE)) {
                $lockOption = SelectLockOption::get(SelectLockOption::FOR_UPDATE);
            } else {
                $tokenList->expectKeyword(Keyword::SHARE);
                $lockOption = SelectLockOption::get(SelectLockOption::FOR_SHARE);
            }
            if ($tokenList->hasKeyword(Keyword::OF)) {
                $lockTables = [];
                do {
                    $lockTables[] = new QualifiedName(...$tokenList->expectQualifiedName());
                } while ($tokenList->hasComma());
            }
            $lockWaitOption = null;
            if ($tokenList->hasKeyword(Keyword::NOWAIT)) {
                $lockWaitOption = SelectLockWaitOption::get(SelectLockWaitOption::NO_WAIT);
            } elseif ($tokenList->hasKeywords(Keyword::SKIP, Keyword::LOCKED)) {
                $lockWaitOption = SelectLockWaitOption::get(SelectLockWaitOption::SKIP_LOCKED);
            }
            $locking = new SelectLocking($lockOption, $lockWaitOption, $lockTables);
        }

        if ($into === null && $tokenList->hasKeyword(Keyword::INTO)) {
            $into = $this->parseInto($tokenList);
        }

        return new SelectCommand($what, $from, $where, $groupBy, $having, $with, $windows, $orderBy, $limit, $offset, $distinct, $options, $into, $locking, $withRollup);
    }

    /**
     * TABLE table_name [ORDER BY column_name] [LIMIT number [OFFSET number]]
     *   [into_option]
     */
    public function parseTable(TokenList $tokenList): TableCommand
    {
        $tokenList->expectKeyword(Keyword::TABLE);
        $name = $tokenList->expectQualifiedName();

        [$orderBy, $limit, $offset, $into] = $this->parseOrderLimitOffsetInto($tokenList);

        return new TableCommand($name, $orderBy, $limit, $offset, $into);
    }

    /**
     * VALUES row_constructor_list [ORDER BY column_designator] [LIMIT number]
     *
     * row_constructor_list:
     *   ROW(value_list)[, ROW(value_list)][, ...]
     *
     * value_list:
     *   value[, value][, ...]
     *
     * column_designator:
     *   column_index
     */
    public function parseValues(TokenList $tokenList): ValuesCommand
    {
        $tokenList->expectKeyword(Keyword::TABLE);
        $rows = [];
        do {
            $tokenList->expectKeyword(Keyword::ROW);
            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            $values = [];
            do {
                $values[] = $this->expressionParser->parseExpression($tokenList);
            } while($tokenList->hasComma());
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            $rows[] = new Row($values);
        } while($tokenList->hasComma());

        [$orderBy, $limit, , $into] = $this->parseOrderLimitOffsetInto($tokenList, false);

        return new ValuesCommand($rows, $orderBy, $limit, $into);
    }

    /**
     * @return array{OrderByExpression|null, int|null, int|null, SelectInto|null}
     */
    private function parseOrderLimitOffsetInto(TokenList $tokenList, $parseOffset = true): array
    {
        $orderBy = $limit = $offset = $into = null;
        if ($tokenList->hasKeywords(Keyword::ORDER, Keyword::BY)) {
            $orderBy = $tokenList->expectName();
        }
        if ($tokenList->hasKeyword(Keyword::LIMIT)) {
            $limit = $tokenList->expectInt();
            if ($parseOffset && $tokenList->hasKeyword(Keyword::OFFSET)) {
                $offset = $tokenList->expectInt();
            }
        }
        if ($into === null && $tokenList->hasKeyword(Keyword::INTO)) {
            $into = $this->parseInto($tokenList);
        }

        return [$orderBy, $limit, $offset, $into];
    }

    /**
     * into_option: {
     *     INTO OUTFILE 'file_name'
     *       [CHARACTER SET charset_name]
     *       export_options
     *   | INTO DUMPFILE 'file_name'
     *   | INTO var_name [, var_name] ...
     * }
     */
    private function parseInto(TokenList $tokenList): SelectInto
    {
        if ($tokenList->hasKeyword(Keyword::OUTFILE)) {
            $outFile = $tokenList->expectString();
            $charset = null;
            if ($tokenList->hasKeywords(Keyword::CHARACTER, Keyword::SET) || $tokenList->hasKeyword(Keyword::CHARSET)) {
                $charset = $tokenList->expectNameOrStringEnum(Charset::class);
            }
            $format = $this->fileFormatParser->parseFormat($tokenList);

            return new SelectInto(null, null, $outFile, $charset, $format);
        } elseif ($tokenList->hasKeyword(Keyword::DUMPFILE)) {
            $dumpFile = $tokenList->expectString();

            return new SelectInto(null, $dumpFile);
        } else {
            $variables = [];
            do {
                /** @var string $expression */
                $expression = $tokenList->expect(TokenType::AT_VARIABLE)->value;
                $variables[] = $expression;
            } while ($tokenList->hasComma());

            return new SelectInto($variables);
        }
    }

    /**
     * window_spec:
     *   [window_name] [partition_clause] [order_clause] [frame_clause]
     *
     * partition_clause:
     *   PARTITION BY expr [, expr] ...
     *
     * order_clause:
     *   ORDER BY expr [ASC|DESC] [, expr [ASC|DESC]] ...
     *
     * frame_clause:
     *   frame_units frame_extent
     *
     * frame_units:
     *   {ROWS | RANGE}
     *
     * frame_extent:
     *   {frame_start | frame_between}
     *
     * frame_between:
     *   BETWEEN frame_start AND frame_end
     */
    private function parseWindow(TokenList $tokenList): WindowSpecification
    {
        $reference = $tokenList->getName();

        $partitionBy = $orderBy = $frame = null;
        if ($tokenList->hasKeywords(Keyword::PARTITION, Keyword::BY)) {
            $partitionBy = [];
            do {
                $partitionBy[] = $this->expressionParser->parseExpression($tokenList);
            } while ($tokenList->hasComma());
        }

        if ($tokenList->hasKeywords(Keyword::ORDER, Keyword::BY)) {
            $orderBy = $this->expressionParser->parseOrderBy($tokenList);
        }

        $keyword = $tokenList->getAnyKeyword(Keyword::ROWS, Keyword::RANGE);
        if ($keyword !== null) {
            $units = WindowFrameUnits::get($keyword);
            $startType = $endType = $startExpression = $endExpression = null;
            if ($tokenList->hasKeyword(Keyword::BETWEEN)) {
                $this->parseFrameBorder($tokenList, $startType, $startExpression);
                $tokenList->expectKeyword(Keyword::AND);
                $this->parseFrameBorder($tokenList, $endType, $endExpression);
            } else {
                $this->parseFrameBorder($tokenList, $startType, $startExpression);
            }

            $frame = new WindowFrame($units, $startType, $endType, $startExpression, $endExpression);
        }

        return new WindowSpecification($reference, $partitionBy, $orderBy, $frame);
    }

    /**
     * frame_start, frame_end: {
     *     CURRENT ROW
     *   | UNBOUNDED PRECEDING
     *   | UNBOUNDED FOLLOWING
     *   | expr PRECEDING
     *   | expr FOLLOWING
     * }
     */
    private function parseFrameBorder(TokenList $tokenList, ?WindowFrameType &$type, ?ExpressionNode &$expression): void
    {
        if ($tokenList->hasKeywords(Keyword::CURRENT, Keyword::ROW)) {
            $type = WindowFrameType::get(WindowFrameType::CURRENT_ROW);
        } elseif ($tokenList->hasKeywords(Keyword::UNBOUNDED, Keyword::PRECEDING)) {
            $type = WindowFrameType::get(WindowFrameType::UNBOUNDED_PRECEDING);
        } elseif ($tokenList->hasKeywords(Keyword::UNBOUNDED, Keyword::FOLLOWING)) {
            $type = WindowFrameType::get(WindowFrameType::UNBOUNDED_FOLLOWING);
        } else {
            $expression = $this->expressionParser->parseExpression($tokenList);
            $keyword = $tokenList->expectAnyKeyword(Keyword::PRECEDING, Keyword::FOLLOWING);
            $type = WindowFrameType::get($keyword);
        }
    }

}
