<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use SqlFtw\Platform\Mode;
use SqlFtw\Sql\Dml\OrderByExpression;
use SqlFtw\Sql\Expression\BinaryLiteral;
use SqlFtw\Sql\Expression\BinaryOperator;
use SqlFtw\Sql\Expression\CaseExpression;
use SqlFtw\Sql\Expression\CollateExpression;
use SqlFtw\Sql\Expression\CurlyExpression;
use SqlFtw\Sql\Expression\ExistsExpression;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\HexadecimalLiteral;
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
use SqlFtw\Sql\Expression\TernaryOperator;
use SqlFtw\Sql\Expression\UnaryOperator;
use SqlFtw\Sql\Expression\Unknown;
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
        $operators = [Operator::OR, Operator::XOR, Operator::AND, Operator::AMPERSANDS];
        if (!$tokenList->getSettings()->getMode()->contains(Mode::PIPES_AS_CONCAT)) {
            $operators[] = Operator::PIPES;
        }

        if ($tokenList->mayConsumeOperator(Operator::NOT)) {
            $expr = $this->parseExpression($tokenList);

            return new UnaryOperator(Operator::NOT, $expr);
        } elseif ($tokenList->mayConsumeOperator(Operator::EXCLAMATION)) {
            $expr = $this->parseExpression($tokenList);

            return new UnaryOperator(Operator::EXCLAMATION, $expr);
        }

        $left = $this->parseBooleanPrimary($tokenList);
        $operator = $tokenList->mayConsumeAnyOperator($operators);
        if ($operator !== null) {
            $right = $this->parseExpression($tokenList);

            return new BinaryOperator($left, [$operator], $right);
        } elseif ($tokenList->mayConsumeKeyword(Keyword::IS)) {
            $not = (bool) $tokenList->mayConsumeKeyword(Keyword::NOT);
            $keyword = $tokenList->consumeAnyKeyword(Keyword::TRUE, Keyword::FALSE, Keyword::UNKNOWN);
            $right = $keyword === Keyword::UNKNOWN
                ? new Unknown()
                : new Literal($keyword === Keyword::TRUE ? true : false);

            return new BinaryOperator($left, $not ? [Operator::NOT, Operator::IS] : [Operator::IS], $right);
        } else {
            return $left;
        }
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
        $operators = [
            Operator::SAFE_EQUAL, Operator::EQUAL, Operator::GREATER_OR_EQUAL, Operator::GREATER,
            Operator::LESS_OR_EQUAL, Operator::LESS, Operator::LESS_OR_GREATER, Operator::NON_EQUAL
        ];

        $left = $this->parsePredicate($tokenList);
        $operator = $tokenList->mayConsumeAnyOperator($operators);
        if ($operator !== null) {
            $quantifier = $tokenList->mayConsumeAnyKeyword(Keyword::ALL, Keyword::ANY);
            if ($quantifier !== null) {
                $tokenList->consume(TokenType::LEFT_PARENTHESIS);
                $subquery = new Parentheses($this->parseSubquery($tokenList));
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

                return new BinaryOperator($left, [$operator, $quantifier], $subquery);
            } else {
                $right = $this->parsePredicate($tokenList);

                return new BinaryOperator($left, [$operator], $right);
            }
        } elseif ($tokenList->mayConsumeKeyword(Keyword::IS)) {
            $not = (bool) $tokenList->mayConsumeKeyword(Keyword::NOT);
            $tokenList->consumeKeyword(Keyword::NULL);
            $right = new Literal(null);

            return new BinaryOperator($left, $not ? [Operator::IS, Operator::NOT] : [Operator::IS], $right);
        } else {
            return $left;
        }
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
        $left = $this->parseBitExpression($tokenList);
        if ($tokenList->mayConsumeKeywords(Keyword::SOUNDS, Keyword::LIKE)) {
            $right = $this->parseBitExpression($tokenList);

            return new BinaryOperator($left, [Operator::SOUNDS, Operator::LIKE], $right);
        }

        $not = (bool) $tokenList->mayConsumeKeyword(Keyword::NOT);

        if ($operator = $tokenList->mayConsumeAnyKeyword(Keyword::REGEXP, Keyword::RLIKE)) {
            $right = $this->parseBitExpression($tokenList);

            return new BinaryOperator($left, $not ? [Operator::NOT, $operator] : [$operator], $right);
        } elseif ($tokenList->mayConsumeKeyword(Keyword::BETWEEN)) {
            $middle = $this->parseBitExpression($tokenList);
            $tokenList->consumeKeyword(Keyword::AND);
            $right = $this->parseBitExpression($tokenList);

            return new TernaryOperator($left, $not ? [Operator::NOT, Operator::BETWEEN] : [Operator::BETWEEN], $middle, Operator::AND, $right);
        } elseif ($tokenList->mayConsumeKeyword(Keyword::IN)) {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            if ($tokenList->mayConsumeKeyword(Keyword::SELECT)) {
                $subquery = new Parentheses($this->parseSubquery($tokenList->resetPosition(-1)));
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

                return new BinaryOperator($left, $not ? [Operator::NOT, Operator::IN] : [Operator::IN], $subquery);
            } else {
                $expressions = new Parentheses(new ListExpression($this->parseExpressionList($tokenList)));
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

                return new BinaryOperator($left, $not ? [Operator::NOT, Operator::IN] : [Operator::IN], $expressions);
            }
        } elseif ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
            $second = $this->parseSimpleExpression($tokenList);
            if ($tokenList->mayConsumeKeyword(Keyword::ESCAPE)) {
                $third = $this->parseSimpleExpression($tokenList);

                return new TernaryOperator($left, $not ? [Operator::NOT, Operator::LIKE] : [Operator::LIKE], $second, Operator::ESCAPE, $third);
            } else {
                return new BinaryOperator($left, $not ? [Operator::NOT, Operator::LIKE] : [Operator::LIKE], $second);
            }
        } else {
            return $left;
        }
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
        $operators = [
            Operator::BIT_OR, Operator::BIT_AND, Operator::LEFT_SHIFT, Operator::RIGHT_SHIFT, Operator::PLUS, Operator::MINUS,
            Operator::MULTIPLY, Operator::DIVIDE, Operator::DIV, Operator::MOD, Operator::MODULO, Operator::BIT_XOR
        ];

        $left = $this->parseSimpleExpression($tokenList);
        $operator = $tokenList->mayConsumeAnyOperator(...$operators);
        if ($operator === null) {
            return $left;
        }

        if (($operator === Operator::PLUS || $operator === Operator::MINUS) && $tokenList->mayConsumeKeyword(Keyword::INTERVAL)) {
            $right = new IntervalExpression($this->parseInterval($tokenList));

            return new BinaryOperator($left, [$operator], $right);
        }
        $right = $this->parseBitExpression($tokenList);

        return new BinaryOperator($left, [$operator], $right);
    }

    /**
     * simple_expr:
     *     + simple_expr
     *   | - simple_expr
     *   | ~ simple_expr
     *   | ! simple_expr
     *   | BINARY simple_expr
     *   | EXISTS (subquery)
     *   | (subquery)
     *   | (expr [, expr] ...)
     *   | ROW (expr, expr [, expr] ...)
     *   | interval_expr
     *   | case_expr
     *   | match_expr
     *   | param_marker
     *   | {identifier expr}
     *
     *   | variable
     *   | identifier
     *   | function_call
     *
     *   | literal
     *
     *   | simple_expr COLLATE collation_name
     *   | simple_expr || simple_expr
     */
    private function parseSimpleExpression(TokenList $tokenList): ExpressionNode
    {
        $expression = null;
        $operator = $tokenList->mayConsumeAnyOperator(
            Operator::PLUS, Operator::MINUS, Operator::BIT_INVERT, Operator::EXCLAMATION, Operator::BINARY
        );
        if ($operator !== null) {
            // + simple_expr
            // - simple_expr
            // ~ simple_expr
            // ! simple_expr
            // BINARY simple_expr
            $expression = new UnaryOperator($operator, $this->parseSimpleExpression($tokenList));

        } elseif ($tokenList->mayConsumeKeyword(Keyword::EXISTS)) {
            // EXISTS (subquery)
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $subquery = $this->parseSubquery($tokenList);
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            $expression = new ExistsExpression($subquery);

        } elseif ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
            if ($tokenList->mayConsumeAnyKeyword(Keyword::SELECT)) {
                // (subquery)
                $subquery = $this->parseSubquery($tokenList->resetPosition(-1));
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
                $expression = new Parentheses($subquery);
            } else {
                // (expr [, expr] ...)
                $expressions = $this->parseExpressionList($tokenList);
                $expression = new Parentheses(new ListExpression($expressions));
            }
        } elseif ($tokenList->mayConsumeKeyword(Keyword::ROW)) {
            // ROW (expr, expr [, expr] ...)
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $expressions = $this->parseExpressionList($tokenList);
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            $expression = new RowExpression($expressions);

        } elseif ($tokenList->mayConsumeKeyword(Keyword::INTERVAL)) {
            // interval_expr
            $interval = $this->parseInterval($tokenList);
            $expression = new IntervalExpression($interval);

        } elseif ($tokenList->mayConsumeKeyword(Keyword::CASE)) {
            // case_expr
            $expression = $this->parseCase($tokenList);

        } elseif ($tokenList->mayConsumeKeyword(Keyword::MATCH)) {
            // match_expr
            $expression = $this->parseMatch($tokenList);

        } elseif ($tokenList->mayConsumeKeyword(TokenType::PLACEHOLDER)) {
            // param_marker
            $expression = new Placeholder();

        } elseif ($tokenList->mayConsume(TokenType::LEFT_CURLY_BRACKET)) {
            // {identifier expr}
            $name = $tokenList->consumeName();
            $expression = $this->parseExpression($tokenList);
            $tokenList->consume(TokenType::RIGHT_CURLY_BRACKET);
            $expression = new CurlyExpression($name, $expression);

        } elseif ($variable = $tokenList->mayConsume(TokenType::AT_VARIABLE)) {
            // variable
            if ($variable[1] === '@') {
                // @@global.xyz
                $tokenList->consume(TokenType::DOT);
                $variable .= '.' . $tokenList->consumeName();
            }
            $expression = new Identifier($variable);

        } elseif ($name1 = $tokenList->mayConsumeName()) {
            $name2 = $name3 = null;
            if ($tokenList->mayConsume(TokenType::DOT)) {
                $name2 = $tokenList->consumeName();
                if ($tokenList->mayConsume(TokenType::DOT)) {
                    $name3 = $tokenList->consumeName();
                }
            }
            if ($name3 !== null) {
                // identifier
                $expression = new Identifier(new ColumnName($name1, $name2, $name3));

            } elseif ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
                // function_call
                ///
            } elseif ($name2 !== null) {
                // identifier
                $expression = new Identifier(new ColumnName(null, $name1, $name2));

            } else {
                // identifier
                /// constant?
                $expression = new Identifier(new ColumnName(null, null, $name1));
            }
        } else {
            // literal
            $literal = $this->parseLiteralValue($tokenList);
            $expression = new Literal($literal);
        }

        if ($tokenList->mayConsumeKeyword(Keyword::COLLATE)) {
            // simple_expr COLLATE collation_name
            $collation = $tokenList->consumeString();

            return new CollateExpression($expression, $collation);
        } elseif ($tokenList->getSettings()->getMode()->contains(Mode::PIPES_AS_CONCAT) && $tokenList->mayConsumeOperator(Operator::PIPES)) {
            // simple_expr || simple_expr
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
     * @return string|int|float|bool|\SqlFtw\Sql\Expression\BinaryLiteral|\SqlFtw\Sql\Expression\HexadecimalLiteral|null
     */
    public function parseLiteralValue(TokenList $tokenList)
    {
        $token = $tokenList->consume(TokenType::VALUE);

        $value = $token->value;
        if ($token->type & TokenType::BINARY_LITERAL) {
            $value = new BinaryLiteral($value);
        } elseif ($token->type & TokenType::HEXADECIMAL_LITERAL) {
            $value = new HexadecimalLiteral($value);
        }

        return $value;
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
