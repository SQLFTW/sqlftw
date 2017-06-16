<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use SqlFtw\Sql\Dml\OrderByExpression;
use SqlFtw\Sql\Expression\BinaryOperator;
use SqlFtw\Sql\Expression\CaseExpression;
use SqlFtw\Sql\Expression\CollateExpression;
use SqlFtw\Sql\Expression\CurlyExpression;
use SqlFtw\Sql\Expression\ExistsExpression;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\IntervalExpression;
use SqlFtw\Sql\Expression\ListExpression;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\MatchExpression;
use SqlFtw\Sql\Expression\MatchMode;
use SqlFtw\Sql\Expression\Parentheses;
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\RowExpression;
use SqlFtw\Sql\Expression\Subquery;
use SqlFtw\Sql\Expression\UnaryOperator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Names\ColumnName;
use SqlFtw\Sql\Operator;
use SqlFtw\Sql\Order;
use SqlFtw\Sql\Time\TimeExpression;
use SqlFtw\Sql\Time\TimeInterval;
use SqlFtw\Sql\Time\TimeIntervalUnit;

class ExpressionParser
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\ParserFactory */
    private $parserFactory;

    public function __construct(ParserFactory $parserFactory)
    {
        $this->parserFactory = $parserFactory;
    }

    /**
     * expr:
     *     expr OR expr
     *   | expr || expr
     *   | expr XOR expr
     *   | expr AND expr
     *   | expr && expr
     *   | NOT expr
     *   | ! expr
     *   | boolean_primary IS [NOT] {TRUE | FALSE | UNKNOWN}
     *   | boolean_primary
     */
    public function parseExpression(TokenList $tokenList): ExpressionNode
    {
        if ($tokenList->mayConsumeOperator(Operator::NOT)) {

        } elseif ($tokenList->mayConsumeOperator(Operator::EXCLAMATION)) {

        }

        return new ExpressionNode();
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Expression\ExpressionNode[]
     */
    private function parseExpressionList(TokenList $tokenList): array
    {
        $expressions = [];
        do {
            $expressions[] = $this->parseExpression($tokenList);
        } while ($tokenList->mayConsumeComma());

        return $expressions;
    }

    /**
     * boolean_primary:
     *     boolean_primary IS [NOT] NULL
     *   | boolean_primary <=> predicate
     *   | boolean_primary comparison_operator predicate
     *   | boolean_primary comparison_operator {ALL | ANY} (subquery)
     *   | predicate
     *
     * comparison_operator: = | >= | > | <= | < | <> | !=
     */
    private function parseBooleanPrimary(TokenList $tokenList): ExpressionNode
    {

    }

    /**
     * predicate:
     *     bit_expr [NOT] IN (subquery)
     *   | bit_expr [NOT] IN (expr [, expr] ...)
     *   | bit_expr [NOT] BETWEEN bit_expr AND predicate
     *   | bit_expr SOUNDS LIKE bit_expr
     *   | bit_expr [NOT] LIKE simple_expr [ESCAPE simple_expr]
     *   | bit_expr [NOT] REGEXP bit_expr
     *   | bit_expr
     */
    private function parsePredicate(TokenList $tokenList): ExpressionNode
    {
        ///
    }

    /**
     * bit_expr:
     *     bit_expr | bit_expr
     *   | bit_expr & bit_expr
     *   | bit_expr << bit_expr
     *   | bit_expr >> bit_expr
     *   | bit_expr + bit_expr
     *   | bit_expr - bit_expr
     *   | bit_expr * bit_expr
     *   | bit_expr / bit_expr
     *   | bit_expr DIV bit_expr
     *   | bit_expr MOD bit_expr
     *   | bit_expr % bit_expr
     *   | bit_expr ^ bit_expr
     *   | bit_expr + interval_expr
     *   | bit_expr - interval_expr
     *   | simple_expr
     */
    private function parseBitExpression(TokenList $tokenList): ExpressionNode
    {
        ///
    }

    /**
     * simple_expr:
     *     literal
     *   | identifier
     *   | function_call
     *   | simple_expr COLLATE collation_name
     *   | param_marker
     *   | variable
     *   | simple_expr || simple_expr
     *   | + simple_expr
     *   | - simple_expr
     *   | ~ simple_expr
     *   | ! simple_expr
     *   | BINARY simple_expr
     *   | (expr [, expr] ...)
     *   | ROW (expr, expr [, expr] ...)
     *   | (subquery)
     *   | EXISTS (subquery)
     *   | {identifier expr}
     *   | match_expr
     *   | case_expr
     *   | interval_expr
     */
    private function parseSimpleExpression(TokenList $tokenList): ExpressionNode
    {
        $expression = null;
        $operator = $tokenList->mayConsumeAnyOperator(
            Operator::PLUS, Operator::MINUS, Operator::BIT_INVERT, Operator::EXCLAMATION, Operator::BINARY
        );
        if ($operator !== null) {
            $expression = new UnaryOperator($operator, $this->parseSimpleExpression($tokenList));

        } elseif ($tokenList->mayConsumeKeyword(Keyword::EXISTS)) {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $subquery = $this->parseSubquery($tokenList);
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            $expression = new ExistsExpression($subquery);

        } elseif ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
            if ($tokenList->mayConsumeAnyKeyword(Keyword::SELECT)) {
                $subquery = $this->parseSubquery($tokenList->resetPosition(-1));
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
                $expression = new Parentheses($subquery);
            } else {
                $expressions = $this->parseExpressionList($tokenList);
                $expression = new Parentheses(new ListExpression($expressions));
            }
        } elseif ($tokenList->mayConsumeKeyword(Keyword::ROW)) {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $expressions = $this->parseExpressionList($tokenList);
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            $expression = new RowExpression($expressions);

        } elseif ($tokenList->mayConsumeKeyword(Keyword::INTERVAL)) {
            $interval = $this->parseInterval($tokenList);
            $expression = new IntervalExpression($interval);

        } elseif ($tokenList->mayConsumeKeyword(Keyword::CASE)) {
            $expression = $this->parseCase($tokenList);

        } elseif ($tokenList->mayConsumeKeyword(Keyword::MATCH)) {
            $expression = $this->parseMatch($tokenList);

        } elseif ($tokenList->mayConsumeKeyword(TokenType::PLACEHOLDER)) {
            $expression = new Placeholder();

        } elseif ($tokenList->mayConsume(TokenType::LEFT_CURLY_BRACKET)) {
            $name = $tokenList->consumeName();
            $expression = $this->parseExpression($tokenList);
            $tokenList->consume(TokenType::RIGHT_CURLY_BRACKET);
            $expression = new CurlyExpression($name, $expression);

        } elseif ($name = $tokenList->mayConsume(TokenType::AT_VARIABLE)) {
            $expression = new Identifier($name);

        } else {

            $name = $tokenList->mayConsumeQualifiedName();
            if ($name !== null) {
                if ($name[0] === '@') {
                    $expression = new Identifier($name);
                } else {

                }
            } else {
                $literal = $this->parseLiteralValue($tokenList);
                $expression = new Literal($literal);
            }
        }

        if ($tokenList->mayConsumeKeyword(Keyword::COLLATE)) {
            $collation = $tokenList->consumeString();

            return new CollateExpression($expression, $collation);
        } elseif ($tokenList->getSettings()->pipesAsConcat() && $tokenList->mayConsumeOperator(Operator::PIPES)) {
            $right = $this->parseSimpleExpression($tokenList);

            return new BinaryOperator($expression, Operator::PIPES, $right);
        } else {
            return $expression;
        }
    }

    /**
     * CASE value WHEN [compare_value] THEN result [WHEN [compare_value] THEN result ...] [ELSE result] END
     *
     * CASE WHEN [condition] THEN result [WHEN [condition] THEN result ...] [ELSE result] END
     */
    private function parseCase(TokenList $tokenList): CaseExpression
    {
        $condition = null;
        if (!$tokenList->mayConsumeKeyword(Keyword::WHEN)) {
            $condition = new Literal($this->parseLiteralValue($tokenList));
            $tokenList->consumeKeyword(Keyword::WHEN);
        }
        $values = $results = [];
        do {
            if ($condition !== null) {
                $values[] = new Literal($this->parseLiteralValue($tokenList));
            } else {
                $values[] = $this->parseExpression($tokenList);
            }
            $tokenList->consumeKeyword(Keyword::THEN);
            $results[] = new Literal($this->parseLiteralValue($tokenList));
        } while ($tokenList->mayConsumeKeyword(Keyword::WHEN));

        if ($tokenList->mayConsumeKeyword(Keyword::ELSE)) {
            $results[] = new Literal($this->parseLiteralValue($tokenList));
        }
        $tokenList->consumeKeywords(Keyword::END, Keyword::CASE);

        return new CaseExpression($condition, $values, $results);
    }

    /**
     * MATCH (col1,col2,...) AGAINST (expr [search_modifier])
     *
     * search_modifier:
     *     IN NATURAL LANGUAGE MODE
     *   | IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION
     *   | IN BOOLEAN MODE
     *   | WITH QUERY EXPANSION
     */
    private function parseMatch(TokenList $tokenList): MatchExpression
    {
        $tokenList->consume(TokenType::LEFT_PARENTHESIS);
        $columns = [];
        do {
            $columns[] = new ColumnName(...$tokenList->consumeColumnName());
        } while ($tokenList->mayConsumeComma());
        $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

        $tokenList->consumeKeyword(Keyword::AGAINST);
        $tokenList->consume(TokenType::LEFT_PARENTHESIS);
        $query = $tokenList->consumeString();
        /** @var \SqlFtw\Sql\Expression\MatchMode|null $mode */
        $mode = $tokenList->mayConsumeEnum(MatchMode::class);
        $expansion = (bool) $tokenList->mayConsumeKeywords(Keyword::WITH, Keyword::QUERY, Keyword::EXPANSION);
        $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

        return new MatchExpression($columns, $query, $mode, $expansion);
    }

    private function parseSubquery(TokenList $tokenList): Subquery
    {
        return new Subquery($this->parserFactory->getSelectCommandParser()->parseSelect($tokenList));
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @param int|null $position
     * @return string|int|float|bool|null
     */
    public function parseLiteralValue(TokenList $tokenList)
    {

        ///
        return '';
    }

    /**
     * order_by:
     *     [ORDER BY {col_name | expr | position} [ASC | DESC], ...]
     *
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Dml\OrderByExpression[]
     */
    public function parseOrderBy(TokenList $tokenList): array
    {
        $orderBy = [];
        do {
            $expression = $this->parseExpression($tokenList);
            /// todo: extract column name or position from expression

            /** @var \SqlFtw\Sql\Order $order */
            $order = $tokenList->mayConsumeEnum(Order::class);
            $orderBy[] = new OrderByExpression($order, null, $expression);
        } while ($tokenList->mayConsumeComma());

        return $orderBy;
    }

    /**
     * limit:
     *     [LIMIT {[offset,] row_count | row_count OFFSET offset}]
     *
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return int[] ($limit, $offset)
     */
    public function parseLimitAndOffset(TokenList $tokenList): array
    {
        $limit = $tokenList->consumeInt();
        $offset = null;
        if ($tokenList->mayConsumeKeyword(Keyword::OFFSET)) {
            $offset = $tokenList->consumeInt();
        } elseif ($tokenList->mayConsumeComma()) {
            $offset = $limit;
            $limit = $tokenList->consumeInt();
        }

        return [$limit, $offset];
    }

    /**
     * expression:
     *     timestamp [+ INTERVAL interval] ...
     */
    public function parseTimeExpression(TokenList $tokenList): TimeExpression
    {
        $time = $tokenList->consumeDateTime();
        $intervals = [];
        while ($tokenList->mayConsumeOperator(Operator::PLUS)) {
            $tokenList->consumeKeyword(Keyword::INTERVAL);
            $intervals[] = $this->parseInterval($tokenList);
        }
        return new TimeExpression($time, $intervals);
    }

    /**
     * interval:
     *     quantity {YEAR | QUARTER | MONTH | DAY | HOUR | MINUTE |
     *          WEEK | SECOND | YEAR_MONTH | DAY_HOUR | DAY_MINUTE |
     *          DAY_SECOND | HOUR_MINUTE | HOUR_SECOND | MINUTE_SECOND}
     */
    public function parseInterval(TokenList $tokenList): TimeInterval
    {
        $value = $tokenList->mayConsumeString();
        if ($value === null) {
            $value = $tokenList->consumeInt();
        }
        /** @var \SqlFtw\Sql\Time\TimeIntervalUnit $unit */
        $unit = $tokenList->consumeEnum(TimeIntervalUnit::class);

        return new TimeInterval($value, $unit);
    }

}
