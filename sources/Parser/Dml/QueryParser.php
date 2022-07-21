<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\ParserException;
use SqlFtw\Parser\ParserFactory;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\Delete\DeleteCommand;
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
use SqlFtw\Sql\Dml\Query\TableCommand;
use SqlFtw\Sql\Dml\Query\UnionExpression;
use SqlFtw\Sql\Dml\Query\UnionType;
use SqlFtw\Sql\Dml\Query\ValuesCommand;
use SqlFtw\Sql\Dml\Query\WindowFrame;
use SqlFtw\Sql\Dml\Query\WindowFrameType;
use SqlFtw\Sql\Dml\Query\WindowFrameUnits;
use SqlFtw\Sql\Dml\Query\WindowSpecification;
use SqlFtw\Sql\Dml\Update\UpdateCommand;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Dml\WithExpression;
use SqlFtw\Sql\Entity;
use SqlFtw\Sql\Expression\Asterisk;
use SqlFtw\Sql\Expression\DefaultLiteral;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Expression\UserVariable;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Order;
use SqlFtw\Sql\Statement;
use function array_pop;
use function count;

class QueryParser
{
    use StrictBehaviorMixin;

    /** @var ParserFactory */
    private $parserFactory;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var TableReferenceParser */
    private $tableReferenceParser;

    public function __construct(
        ParserFactory $parserFactory,
        ExpressionParser $expressionParser,
        TableReferenceParser $tableReferenceParser
    ) {
        $this->parserFactory = $parserFactory;
        $this->expressionParser = $expressionParser;
        $this->tableReferenceParser = $tableReferenceParser;
    }

    /**
     * with_clause:
     *   WITH [RECURSIVE]
     *     cte_name [(col_name [, col_name] ...)] AS (subquery)
     *     [, cte_name [(col_name [, col_name] ...)] AS (subquery)] ...
     *
     * @return Statement&(Query|UpdateCommand|DeleteCommand)
     */
    public function parseWith(TokenList $tokenList): Command
    {
        $tokenList->expectKeyword(Keyword::WITH);
        $recursive = $tokenList->hasKeyword(Keyword::RECURSIVE);

        $expressions = [];
        do {
            $name = $tokenList->expectName(null);
            $columns = null;
            if ($tokenList->hasSymbol('(')) {
                $columns = [];
                do {
                    $columns[] = $tokenList->expectName(Entity::COLUMN);
                } while ($tokenList->hasSymbol(','));
                $tokenList->expectSymbol(')');
            }
            $tokenList->expectKeyword(Keyword::AS);
            $tokenList->expectSymbol('(');
            $query = $this->parserFactory->getQueryParser()->parseQuery($tokenList);
            $tokenList->expectSymbol(')');

            $expressions[] = new WithExpression($query, $name, $columns);
        } while ($tokenList->hasSymbol(','));

        $with = new WithClause($expressions, $recursive);

        if ($tokenList->hasSymbol('(')) {
            $next = '(';
        } else {
            $next = $tokenList->expectAnyKeyword(Keyword::SELECT, Keyword::UPDATE, Keyword::DELETE);
        }
        switch ($next) {
            case '(':
            case Keyword::SELECT:
                return $this->parserFactory->getQueryParser()->parseQuery($tokenList->rewind(-1), $with);
            case Keyword::UPDATE:
                return $this->parserFactory->getUpdateCommandParser()->parseUpdate($tokenList->rewind(-1), $with);
            case Keyword::DELETE:
                return $this->parserFactory->getDeleteCommandParser()->parseDelete($tokenList->rewind(-1), $with);
            default:
                throw new ShouldNotHappenException('');
        }
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
     *
     * @return Query&Statement
     */
    public function parseQuery(TokenList $tokenList, ?WithClause $with = null): Query
    {
        $queries = [$this->parseQueryBlock($tokenList, $with)];
        $types = [];

        $tokenList->startUnion();
        while ($tokenList->hasKeyword(Keyword::UNION)) {
            if ($tokenList->hasKeyword(Keyword::ALL)) {
                $types[] = UnionType::get(UnionType::ALL);
            } elseif ($tokenList->hasKeyword(Keyword::DISTINCT)) {
                $types[] = UnionType::get(UnionType::DISTINCT);
            } else {
                $types[] = UnionType::get(UnionType::DEFAULT);
            }
            $queries[] = $this->parseQueryBlock($tokenList);
        }
        $tokenList->endUnion();

        if (count($queries) === 1 || count($types) === 0) {
            return $queries[0];
        }

        [$orderBy, $limit, , $into] = $this->parseOrderLimitOffsetInto($tokenList, false);

        $locking = $this->parseLocking($tokenList);

        // order, limit and into of last unparenthesized query belong to the whole union result
        /** @var Query $lastQuery PHPStan assumes it might be null :E */
        $lastQuery = array_pop($queries);

        if ($lastQuery instanceof SelectCommand) {
            $queryLocking = $lastQuery->getLocking();
            if ($queryLocking !== null) {
                if ($locking !== null) {
                    throw new ParserException("Duplicate INTO clause in last query and in UNION.", $tokenList);
                } else {
                    $locking = $queryLocking;
                    $lastQuery = $lastQuery->removeLocking();
                }
            }
        }

        $queryOrderBy = $lastQuery->getOrderBy();
        if ($queryOrderBy !== null) {
            if ($orderBy !== null) {
                throw new ParserException("Duplicate ORDER BY clause in last query and in UNION.", $tokenList);
            } else {
                $orderBy = $queryOrderBy;
                $lastQuery = $lastQuery->removeOrderBy();
            }
        }

        $queryLimit = $lastQuery->getLimit();
        if ($queryLimit !== null) {
            if ($limit !== null) {
                throw new ParserException("Duplicate LIMIT clause in last query and in UNION.", $tokenList);
            } else {
                $limit = $queryLimit;
                $lastQuery = $lastQuery->removeLimit();
            }
        }

        $queryInto = $lastQuery->getInto();
        if ($queryInto !== null) {
            if ($into !== null) {
                throw new ParserException("Duplicate INTO clause in last query and in UNION.", $tokenList);
            } else {
                $into = $queryInto;
                $lastQuery = $lastQuery->removeInto();
            }
        }

        $queries[] = $lastQuery;

        foreach ($queries as $i => $query) {
            if ($query instanceof SelectCommand) {
                if ($query->getLocking() !== null) {
                    throw new ParserException("Locking options are not allowed in UNION without parentheses around query.", $tokenList);
                }
            }
            if ($query->getLimit() !== null) {
                throw new ParserException("LIMIT not allowed in UNION without parentheses around query.", $tokenList);
            }
            if ($query->getOrderBy() !== null) {
                throw new ParserException("ORDER BY not allowed in UNION without parentheses around query.", $tokenList);
            }
            if ($query->getInto() !== null) {
                throw new ParserException("INTO not allowed in UNION or subquery.", $tokenList);
            }
            if ($query instanceof ParenthesizedQueryExpression) {
                if ($i !== 0 && $query->getQuery() instanceof UnionExpression) {
                    throw new ParserException("Nested UNIONs are only allowed on left side.", $tokenList);
                }
                if ($query->containsInto()) {
                    throw new ParserException("INTO not allowed in UNION or subquery.", $tokenList);
                }
            }
        }

        return new UnionExpression($queries, $types, $orderBy, $limit, $into, $locking);
    }

    /**
     * @return Query&Statement
     */
    public function parseQueryBlock(TokenList $tokenList, ?WithClause $with = null): Query
    {
        if ($tokenList->hasSymbol('(')) {
            return $this->parseParenthesizedQueryExpression($tokenList->rewind(-1), $with);
        }

        $keywords = $with !== null
            ? [Keyword::SELECT]
            : [Keyword::SELECT, Keyword::TABLE, Keyword::VALUES, Keyword::WITH];

        $keyword = $tokenList->expectAnyKeyword(...$keywords);
        if ($keyword === Keyword::SELECT) {
            return $this->parseSelect($tokenList->rewind(-1), $with);
        } elseif ($keyword === Keyword::TABLE) {
            return $this->parseTable($tokenList->rewind(-1));
        } elseif ($keyword === Keyword::VALUES) {
            return $this->parseValues($tokenList->rewind(-1));
        } else {
            $statement =  $this->parseWith($tokenList->rewind(-1));
            if (!$statement instanceof SelectCommand) {
                throw new ParserException('Expected SELECT.', $tokenList);
            }
            return $statement;
        }
    }

    private function parseParenthesizedQueryExpression(TokenList $tokenList, ?WithClause $with = null): ParenthesizedQueryExpression
    {
        $tokenList->expectSymbol('(');
        $query = $this->parseQuery($tokenList);
        $tokenList->expectSymbol(')');

        [$orderBy, $limit, $offset, $into] = $this->parseOrderLimitOffsetInto($tokenList);

        if ($orderBy !== null) {
            foreach ($orderBy as $order) {
                $column = $order->getColumn();
                if ($column !== null && !$column instanceof SimpleName) {
                    throw new ParserException('Qualified name in ORDER BY is not allowed in parenthesized query expression.', $tokenList);
                }
            }
        }

        $queryInto = $query->getInto();
        if ($queryInto !== null) {
            if ($into !== null) {
                throw new ParserException("Duplicate INTO clause in query and in parenthesized query expression.", $tokenList);
            } else {
                $into = $queryInto;
                $query = $query->removeInto();
            }
        }

        return new ParenthesizedQueryExpression($query, $with, $orderBy, $limit, $offset, $into);
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
     *
     * @return Query&Statement
     */
    public function parseSelect(TokenList $tokenList, ?WithClause $with = null): Query
    {
        $tokenList->expectKeyword(Keyword::SELECT);

        // phpcs:disable Squiz.Arrays.ArrayDeclaration.ValueNoNewline
        $keywords = [
            Keyword::ALL, Keyword::DISTINCT, Keyword::DISTINCTROW, Keyword::HIGH_PRIORITY, Keyword::STRAIGHT_JOIN,
            Keyword::SQL_SMALL_RESULT, Keyword::SQL_BIG_RESULT, Keyword::SQL_BUFFER_RESULT, Keyword::SQL_CACHE,
            Keyword::SQL_NO_CACHE, Keyword::SQL_CALC_FOUND_ROWS,
        ];
        $distinct = null;
        $options = [];
        while (($keyword = $tokenList->getAnyKeyword(...$keywords)) !== null) {
            switch ($keyword) {
                case Keyword::ALL:
                case Keyword::DISTINCT:
                case Keyword::DISTINCTROW:
                    if ($keyword === Keyword::DISTINCTROW) {
                        $keyword = Keyword::DISTINCT;
                    }
                    if ($distinct !== null) {
                        if (!$distinct->equalsValue($keyword)) {
                            throw new ParserException('Cannot use both DISTINCT and ALL', $tokenList);
                        }
                    }
                    $distinct = SelectDistinctOption::get($keyword);
                    break;
                case Keyword::HIGH_PRIORITY:
                    $options[SelectOption::HIGH_PRIORITY] = true;
                    break;
                case Keyword::STRAIGHT_JOIN:
                    $options[SelectOption::STRAIGHT_JOIN] = true;
                    break;
                case Keyword::SQL_SMALL_RESULT:
                    $options[SelectOption::SMALL_RESULT] = true;
                    break;
                case Keyword::SQL_BIG_RESULT:
                    $options[SelectOption::BIG_RESULT] = true;
                    break;
                case Keyword::SQL_BUFFER_RESULT:
                    $options[SelectOption::BUFFER_RESULT] = true;
                    break;
                case Keyword::SQL_CACHE:
                    if (isset($options[SelectOption::NO_CACHE])) {
                        throw new ParserException('Cannot combine SQL_CACHE and SQL_NO_CACHE options.', $tokenList);
                    }
                    $options[SelectOption::CACHE] = true;
                    break;
                case Keyword::SQL_NO_CACHE:
                    if (isset($options[SelectOption::CACHE])) {
                        throw new ParserException('Cannot combine SQL_CACHE and SQL_NO_CACHE options.', $tokenList);
                    }
                    $options[SelectOption::NO_CACHE] = true;
                    break;
                case Keyword::SQL_CALC_FOUND_ROWS:
                    $options[SelectOption::CALC_FOUND_ROWS] = true;
                    break;
            }
        }

        $what = [];
        do {
            if ($tokenList->hasOperator(Operator::MULTIPLY)) {
                $expression = new Asterisk();
            } else {
                $expression = $this->expressionParser->parseAssignExpression($tokenList);
            }
            $window = null;
            if ($tokenList->hasKeyword(Keyword::OVER)) {
                if ($tokenList->hasSymbol('(')) {
                    $window = $this->parseWindow($tokenList);
                    $tokenList->expectSymbol(')');
                } else {
                    $window = $tokenList->expectName(null);
                }
            }
            $alias = $this->expressionParser->parseAlias($tokenList);
            if ($alias !== null && $expression instanceof QualifiedName && $expression->getName() === '*') {
                throw new ParserException('Cannot use alias after *.', $tokenList);
            }

            $what[] = new SelectExpression($expression, $alias, $window);
        } while ($tokenList->hasSymbol(','));

        $into = null;
        if (!$tokenList->inSubquery()) {
            if ($tokenList->hasKeyword(Keyword::INTO)) {
                $into = $this->parseInto($tokenList);
            }
        }

        $from = null;
        if ($tokenList->hasKeyword(Keyword::FROM)) {
            $from = $this->tableReferenceParser->parseTableReferences($tokenList);
        }

        $where = null;
        if ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseAssignExpression($tokenList);
        }

        $groupBy = null;
        $withRollup = false;
        if ($tokenList->hasKeywords(Keyword::GROUP, Keyword::BY)) {
            $groupBy = [];
            do {
                $expression = $this->expressionParser->parseAssignExpression($tokenList);
                $order = null;
                if ($tokenList->using(Platform::MYSQL, null, 50799)) {
                    $order = $tokenList->getKeywordEnum(Order::class);
                }
                $groupBy[] = new GroupByExpression($expression, $order);
            } while ($tokenList->hasSymbol(','));

            $withRollup = $tokenList->hasKeywords(Keyword::WITH, Keyword::ROLLUP);
        }

        $having = null;
        if ($tokenList->hasKeyword(Keyword::HAVING)) {
            $having = $this->expressionParser->parseAssignExpression($tokenList);
        }

        $windows = null;
        if ($tokenList->hasKeyword(Keyword::WINDOW)) {
            $windows = [];
            do {
                $name = $tokenList->expectName(null);
                $tokenList->expectKeyword(Keyword::AS);

                $tokenList->expectSymbol('(');
                $window = $this->parseWindow($tokenList);
                $tokenList->expectSymbol(')');

                $windows[$name] = $window;
            } while ($tokenList->hasSymbol(','));
        }

        $orderBy = null;
        if ($tokenList->hasKeywords(Keyword::ORDER, Keyword::BY)) {
            $orderBy = $this->expressionParser->parseOrderBy($tokenList);
        }

        $limit = $offset = null;
        if ($tokenList->hasKeyword(Keyword::LIMIT)) {
            $limit = $this->expressionParser->parseLimitOrOffsetValue($tokenList);
            if ($tokenList->hasKeyword(Keyword::OFFSET)) {
                $offset = $this->expressionParser->parseLimitOrOffsetValue($tokenList);
            } elseif ($tokenList->hasSymbol(',')) {
                $offset = $limit;
                $limit = $this->expressionParser->parseLimitOrOffsetValue($tokenList);
            }
        }

        if ($tokenList->hasKeywords(Keyword::PROCEDURE, Keyword::ANALYSE)) {
            $tokenList->expectSymbol('(');
            // todo: ignored
            $this->expressionParser->parseFunctionCall($tokenList, 'PROCEDURE ANALYSE');
        }

        $locking = $this->parseLocking($tokenList);

        if (!$tokenList->inSubquery() && !($tokenList->inUnion() && $from !== null)) {
            if ($into === null && $tokenList->hasKeyword(Keyword::INTO)) {
                $into = $this->parseInto($tokenList);
            }
        }

        if ($locking === null) {
            $locking = $this->parseLocking($tokenList);
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
        $tokenList->expectKeyword(Keyword::VALUES);
        $rows = [];
        do {
            $tokenList->expectKeyword(Keyword::ROW);
            $tokenList->expectSymbol('(');
            $values = [];
            if (!$tokenList->hasSymbol(')') || !$tokenList->inInsert()) {
                do {
                    $value = $this->expressionParser->parseExpression($tokenList);
                    if ($value instanceof DefaultLiteral && !$tokenList->inInsert()) {
                        throw new ParserException('Cannot use DEFAULT in ROW() expression outside and INSERT.', $tokenList);
                    }
                    $values[] = $value;
                } while ($tokenList->hasSymbol(','));
                $tokenList->expectSymbol(')');
            }
            $rows[] = new Row($values);
        } while ($tokenList->hasSymbol(','));

        [$orderBy, $limit, , $into] = $this->parseOrderLimitOffsetInto($tokenList, false);

        return new ValuesCommand($rows, $orderBy, $limit, $into);
    }

    /**
     * @return array{non-empty-array<OrderByExpression>|null, int|SimpleName|Placeholder|null, int|SimpleName|Placeholder|null, SelectInto|null}
     */
    private function parseOrderLimitOffsetInto(TokenList $tokenList, bool $parseOffset = true): array
    {
        $orderBy = $limit = $offset = $into = null;
        if ($tokenList->hasKeywords(Keyword::ORDER, Keyword::BY)) {
            $orderBy = $this->expressionParser->parseOrderBy($tokenList);
        }
        if ($tokenList->hasKeyword(Keyword::LIMIT)) {
            $limit = $this->expressionParser->parseLimitOrOffsetValue($tokenList);
            if ($parseOffset && $tokenList->hasKeyword(Keyword::OFFSET)) {
                $offset = $this->expressionParser->parseLimitOrOffsetValue($tokenList);
            }
        }

        if (!$tokenList->inSubquery()) {
            if ($tokenList->hasKeyword(Keyword::INTO)) {
                $into = $this->parseInto($tokenList);
            }
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
                $charset = $tokenList->expectCharsetName();
            }
            $format = $this->expressionParser->parseFileFormat($tokenList);

            return new SelectInto(null, null, $outFile, $charset, $format);
        } elseif ($tokenList->hasKeyword(Keyword::DUMPFILE)) {
            $dumpFile = $tokenList->expectString();

            return new SelectInto(null, $dumpFile);
        } else {
            $variables = [];
            do {
                $token = $tokenList->get(TokenType::AT_VARIABLE);
                if ($token !== null) {
                    $variable = new UserVariable($token->value);
                } else {
                    $name = $tokenList->expectName(null);
                    $variable = new SimpleName($name);
                }
                $variables[] = $variable;
            } while ($tokenList->hasSymbol(','));

            return new SelectInto($variables);
        }
    }

    /**
     * @return non-empty-array<SelectLocking>|null
     */
    private function parseLocking(TokenList $tokenList): ?array
    {
        $locking = [];
        do {
            $updated = false;
            if ($tokenList->hasKeywords(Keyword::LOCK, Keyword::IN, Keyword::SHARE, Keyword::MODE)) {
                $lockOption = SelectLockOption::get(SelectLockOption::LOCK_IN_SHARE_MODE);
                $locking[] = new SelectLocking($lockOption);
                $updated = true;
            } elseif ($tokenList->hasKeyword(Keyword::FOR)) {
                if ($tokenList->hasKeyword(Keyword::UPDATE)) {
                    $lockOption = SelectLockOption::get(SelectLockOption::FOR_UPDATE);
                } else {
                    $tokenList->expectKeyword(Keyword::SHARE);
                    $lockOption = SelectLockOption::get(SelectLockOption::FOR_SHARE);
                }
                $lockTables = null;
                if ($tokenList->hasKeyword(Keyword::OF)) {
                    $lockTables = [];
                    do {
                        $lockTables[] = $tokenList->expectQualifiedName();
                    } while ($tokenList->hasSymbol(','));
                }
                $lockWaitOption = $tokenList->getMultiKeywordsEnum(SelectLockWaitOption::class);
                $locking[] = new SelectLocking($lockOption, $lockWaitOption, $lockTables);
                $updated = true;
            }
        } while ($updated);

        if ($locking === []) {
            return null;
        }

        return $locking;
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
    public function parseWindow(TokenList $tokenList): WindowSpecification
    {
        $name = $tokenList->getNonReservedName(null);

        $partitionBy = $orderBy = $frame = null;
        if ($tokenList->hasKeywords(Keyword::PARTITION, Keyword::BY)) {
            $partitionBy = [];
            do {
                $partitionBy[] = $this->expressionParser->parseExpression($tokenList);
            } while ($tokenList->hasSymbol(','));
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

        return new WindowSpecification($name, $partitionBy, $orderBy, $frame);
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
    private function parseFrameBorder(TokenList $tokenList, ?WindowFrameType &$type, ?RootNode &$expression): void
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
