<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\Re;
use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use Dogma\Time\DateTime;
use SqlFtw\Platform\Mode;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\ColumnName;
use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\Expression\BinaryLiteral;
use SqlFtw\Sql\Expression\BinaryOperator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\CaseExpression;
use SqlFtw\Sql\Expression\CollateExpression;
use SqlFtw\Sql\Expression\CurlyExpression;
use SqlFtw\Sql\Expression\DataType;
use SqlFtw\Sql\Expression\ExistsExpression;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\HexadecimalLiteral;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\IntervalLiteral;
use SqlFtw\Sql\Expression\ListExpression;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\MatchExpression;
use SqlFtw\Sql\Expression\MatchMode;
use SqlFtw\Sql\Expression\KeywordLiteral;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Expression\Parentheses;
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\RowExpression;
use SqlFtw\Sql\Expression\Subquery;
use SqlFtw\Sql\Expression\TernaryOperator;
use SqlFtw\Sql\Expression\TimeExpression;
use SqlFtw\Sql\Expression\TimeInterval;
use SqlFtw\Sql\Expression\TimeIntervalUnit;
use SqlFtw\Sql\Expression\UnaryOperator;
use SqlFtw\Sql\Expression\ValueLiteral;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Order;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\UserName;
use function explode;
use function in_array;
use function is_int;
use function sprintf;
use function strlen;
use function substr;

class ExpressionParser
{
    use StrictBehaviorMixin;

    private const PUNCTUATION = '[~`@#$%^&\'"\\\\=[\\]{}()<>;:,.?!_|\\/*+-]';

    private const INT_DATETIME_EXPRESSION = '/^(?:[1-9][0-9])?[0-9]{2}(?:0[1-9]|1[012])(?:0[1-9]|[12][0-9]|3[01])(?:[01][0-9]|2[0-3])(?:[0-5][0-9]){2}$/';

    private const STRING_DATETIME_EXPRESSION = '/^((?:[1-9][0-9])?[0-9]{2}'
        . self::PUNCTUATION . '(?:0[1-9]|1[012])'
        . self::PUNCTUATION . '(?:0[1-9]|[12][0-9]|3[01])'
        . '[ T](?:[01][0-9]|2[0-3])'
        . self::PUNCTUATION . '(?:[0-5][0-9])'
        . self::PUNCTUATION . '(?:[0-5][0-9]))'
        . '(\\.[0-9]+)?$/';

    /** @var ParserFactory */
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
        if (!$tokenList->getSettings()->getMode()->containsAny(Mode::PIPES_AS_CONCAT)) {
            $operators[] = Operator::PIPES;
        }

        if ($tokenList->hasOperator(Operator::NOT)) {
            $expr = $this->parseExpression($tokenList);

            return new UnaryOperator(Operator::NOT, $expr);
        } elseif ($tokenList->hasOperator(Operator::EXCLAMATION)) {
            $expr = $this->parseExpression($tokenList);

            return new UnaryOperator(Operator::EXCLAMATION, $expr);
        }

        $left = $this->parseBooleanPrimary($tokenList);
        $operator = $tokenList->getAnyOperator(...$operators);
        if ($operator !== null) {
            $right = $this->parseExpression($tokenList);

            return new BinaryOperator($left, [$operator], $right);
        } elseif ($tokenList->hasKeyword(Keyword::IS)) {
            $not = $tokenList->hasKeyword(Keyword::NOT);
            $keyword = $tokenList->expectAnyKeyword(Keyword::TRUE, Keyword::FALSE, Keyword::UNKNOWN);
            $right = $keyword === Keyword::UNKNOWN
                ? new KeywordLiteral(Keyword::UNKNOWN)
                : new ValueLiteral($keyword === Keyword::TRUE);

            return new BinaryOperator($left, $not ? [Operator::NOT, Operator::IS] : [Operator::IS], $right);
        } else {
            return $left;
        }
    }

    /**
     * @return ExpressionNode[]
     */
    private function parseExpressionList(TokenList $tokenList): array
    {
        $expressions = [];
        do {
            $expressions[] = $this->parseExpression($tokenList);
        } while ($tokenList->hasComma());

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
        static $operators = [
            Operator::SAFE_EQUAL,
            Operator::EQUAL,
            Operator::GREATER_OR_EQUAL,
            Operator::GREATER,
            Operator::LESS_OR_EQUAL,
            Operator::LESS,
            Operator::LESS_OR_GREATER,
            Operator::NON_EQUAL,
        ];

        $left = $this->parsePredicate($tokenList);
        $operator = $tokenList->getAnyOperator(...$operators);
        if ($operator !== null) {
            $quantifier = $tokenList->getAnyKeyword(Keyword::ALL, Keyword::ANY);
            if ($quantifier !== null) {
                $tokenList->expect(TokenType::LEFT_PARENTHESIS);
                $subquery = new Parentheses($this->parseSubquery($tokenList));
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

                return new BinaryOperator($left, [$operator, $quantifier], $subquery);
            } else {
                $right = $this->parsePredicate($tokenList);

                return new BinaryOperator($left, [$operator], $right);
            }
        } elseif ($tokenList->hasKeyword(Keyword::IS)) {
            $not = $tokenList->hasKeyword(Keyword::NOT);
            $tokenList->expectKeyword(Keyword::NULL);
            $right = new KeywordLiteral(Keyword::NULL);

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
        if ($tokenList->hasKeywords(Keyword::SOUNDS, Keyword::LIKE)) {
            $right = $this->parseBitExpression($tokenList);

            return new BinaryOperator($left, [Operator::SOUNDS, Operator::LIKE], $right);
        }

        $not = $tokenList->hasKeyword(Keyword::NOT);

        $operator = $tokenList->getAnyKeyword(Keyword::REGEXP, Keyword::RLIKE);
        if ($operator !== null) {
            $right = $this->parseBitExpression($tokenList);

            return new BinaryOperator($left, $not ? [Operator::NOT, $operator] : [$operator], $right);
        } elseif ($tokenList->hasKeyword(Keyword::BETWEEN)) {
            $middle = $this->parseBitExpression($tokenList);
            $tokenList->expectKeyword(Keyword::AND);
            $right = $this->parseBitExpression($tokenList);

            return new TernaryOperator($left, $not ? [Operator::NOT, Operator::BETWEEN] : [Operator::BETWEEN], $middle, Operator::AND, $right);
        } elseif ($tokenList->hasKeyword(Keyword::IN)) {
            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            if ($tokenList->hasAnyKeyword(Keyword::SELECT, Keyword::TABLE, Keyword::VALUES, Keyword::WITH)) {
                $subquery = new Parentheses($this->parseSubquery($tokenList->resetPosition(-1)));
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

                return new BinaryOperator($left, $not ? [Operator::NOT, Operator::IN] : [Operator::IN], $subquery);
            } else {
                $expressions = new Parentheses(new ListExpression($this->parseExpressionList($tokenList)));
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

                return new BinaryOperator($left, $not ? [Operator::NOT, Operator::IN] : [Operator::IN], $expressions);
            }
        } elseif ($tokenList->hasKeyword(Keyword::LIKE)) {
            $second = $this->parseSimpleExpression($tokenList);
            if ($tokenList->hasKeyword(Keyword::ESCAPE)) {
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
            Operator::BIT_OR,
            Operator::BIT_AND,
            Operator::LEFT_SHIFT,
            Operator::RIGHT_SHIFT,
            Operator::PLUS,
            Operator::MINUS,
            Operator::MULTIPLY,
            Operator::DIVIDE,
            Operator::DIV,
            Operator::MOD,
            Operator::MODULO,
            Operator::BIT_XOR,
        ];

        $left = $this->parseSimpleExpression($tokenList);
        $operator = $tokenList->getAnyOperator(...$operators);
        if ($operator === null) {
            return $left;
        }

        if (($operator === Operator::PLUS || $operator === Operator::MINUS) && $tokenList->hasKeyword(Keyword::INTERVAL)) {
            $right = new IntervalLiteral($this->parseInterval($tokenList));

            // right recursion of interval_expr
            $left = new BinaryOperator($left, [$operator], $right);
            while ($operator = $tokenList->getAnyOperator(Operator::PLUS, Operator::MINUS)) {
                $tokenList->expectKeyword(Keyword::INTERVAL);
                $right = new IntervalLiteral($this->parseInterval($tokenList));
                $left = new BinaryOperator($left, [$operator], $right);
            }

            return $left;
        }
        // full recursion of bit_expr
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
        $operator = $tokenList->getAnyOperator(
            Operator::PLUS,
            Operator::MINUS,
            Operator::BIT_INVERT,
            Operator::EXCLAMATION,
            Operator::BINARY
        );

        if ($operator !== null) {
            // + simple_expr
            // - simple_expr
            // ~ simple_expr
            // ! simple_expr
            // BINARY simple_expr
            $expression = new UnaryOperator($operator, $this->parseSimpleExpression($tokenList));

        } elseif ($tokenList->hasKeyword(Keyword::EXISTS)) {
            // EXISTS (subquery)
            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            $subquery = $this->parseSubquery($tokenList);
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            $expression = new ExistsExpression($subquery);

        } elseif ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
            if ($tokenList->hasAnyKeyword(Keyword::SELECT, Keyword::TABLE, Keyword::VALUES, Keyword::WITH)) {
                // (subquery)
                $subquery = $this->parseSubquery($tokenList->resetPosition(-1));
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
                $expression = new Parentheses($subquery);
            } else {
                // (expr [, expr] ...)
                $expressions = $this->parseExpressionList($tokenList);
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
                $expression = new Parentheses(new ListExpression($expressions));
            }
        } elseif ($tokenList->hasKeyword(Keyword::ROW)) {
            // ROW (expr, expr [, expr] ...)
            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            $expressions = $this->parseExpressionList($tokenList);
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            $expression = new RowExpression($expressions);

        } elseif ($tokenList->hasKeyword(Keyword::INTERVAL)) {
            // interval_expr
            $interval = $this->parseInterval($tokenList);
            $expression = new IntervalLiteral($interval);

        } elseif ($tokenList->hasKeyword(Keyword::CASE)) {
            // case_expr
            $expression = $this->parseCase($tokenList);

        } elseif ($tokenList->hasKeyword(Keyword::MATCH)) {
            // match_expr
            $expression = $this->parseMatch($tokenList);

        } elseif ($tokenList->has(TokenType::PLACEHOLDER)) {
            // param_marker
            $expression = new Placeholder();

        } elseif ($tokenList->has(TokenType::LEFT_CURLY_BRACKET)) {
            // {identifier expr}
            $name = $tokenList->expectName();
            $expression = $this->parseExpression($tokenList);
            $tokenList->expect(TokenType::RIGHT_CURLY_BRACKET);
            $expression = new CurlyExpression($name, $expression);

        } else {
            $variable = $tokenList->get(TokenType::AT_VARIABLE);
            if ($variable !== null) {
                /** @var string $variableName */
                $variableName = $variable->value;
                // variable
                if (in_array(strtoupper($variableName), ['@@SESSION', '@@GOBAL', '@@PERSIST', '@@PERSIST_ONLY'])) {
                    $tokenList->expect(TokenType::DOT);
                    // todo: better type here
                    $variableName .= '.' . $tokenList->expectName();
                }
                $expression = new Identifier($variableName);

            } else {
                $name1 = $tokenList->getName();
                if ($name1 !== null) {
                    $platformFeatures = $tokenList->getSettings()->getPlatform()->getFeatures();
                    $name2 = $name3 = null;
                    if ($tokenList->has(TokenType::DOT)) {
                        if ($tokenList->hasOperator(Operator::MULTIPLY)) {
                            $name2 = '*'; // tbl.*
                        } else {
                            $name2 = $tokenList->expectName();
                        }
                        if ($name2 !== '*' && $tokenList->has(TokenType::DOT)) {
                            if ($tokenList->hasOperator(Operator::MULTIPLY)) {
                                $name3 = '*'; // db.tbl.*
                            } else {
                                $name3 = $tokenList->expectName();
                            }
                        }
                    }
                    if ($name3 !== null) {
                        // identifier
                        $expression = new Identifier(new ColumnName($name3, $name2, $name1));

                    } elseif ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
                        // function_call
                        $expression = $this->parseFunctionCall($tokenList, $name1, $name2);

                    } elseif ($name2 !== null) {
                        // identifier
                        $expression = new Identifier(new ColumnName($name2, $name1, null));

                    } elseif (BuiltInFunction::isValid($name1) && $platformFeatures->isReserved($name1)) {
                        // function without parentheses
                        $expression = new FunctionCall(BuiltInFunction::get($name1));

                    } else {
                        // identifier
                        $expression = new Identifier(new ColumnName($name1, null, null));
                    }
                    // phpcs:disable SlevomatCodingStandard.ControlStructures.AssignmentInCondition
                } elseif (($name1 = $tokenList->get(TokenType::RESERVED)) !== null && $tokenList->has(TokenType::LEFT_PARENTHESIS)) {
                    // function_call
                    $expression = $this->parseFunctionCall($tokenList, $name1->value); // @phpstan-ignore-line string
                } else {
                    // literal
                    $expression = $this->parseLiteral($tokenList);
                }
            }
        }

        if ($tokenList->hasKeyword(Keyword::COLLATE)) {
            // simple_expr COLLATE collation_name
            $collation = $tokenList->expectNameOrStringEnum(Collation::class);

            return new CollateExpression($expression, $collation);
        } elseif ($tokenList->getSettings()->getMode()->containsAny(Mode::PIPES_AS_CONCAT)
            && $tokenList->hasOperator(Operator::PIPES)
        ) {
            // simple_expr || simple_expr
            $right = $this->parseSimpleExpression($tokenList);

            return new BinaryOperator($expression, Operator::PIPES, $right);
        } else {
            return $expression;
        }
    }

    private function parseFunctionCall(TokenList $tokenList, string $name1, ?string $name2 = null): FunctionCall
    {
        $function = $name2 === null && BuiltInFunction::validateValue($name1)
            ? BuiltInFunction::get($name1)
            : new QualifiedName($name2 ?? $name1, $name2 !== null ? $name1 : null);

        if ($tokenList->has(TokenType::RIGHT_PARENTHESIS)) {
            return new FunctionCall($function, []);
        }

        if ($function instanceof BuiltInFunction) {
            $name = $function->getValue();
            if ($name === Keyword::TRIM) {
                return $this->parseTrim($tokenList, $function);
            } elseif ($name === Keyword::JSON_TABLE) {
                return $this->parseJsonTable($tokenList, $function);
            }
            $namedParams = $function->getNamedParams();
        } else {
            $namedParams = [];
        }

        $arguments = [];
        $first = true;
        do {
            if ($tokenList->has(TokenType::RIGHT_PARENTHESIS)) {
                break;
            }
            foreach ($namedParams as $keyword => $type) {
                if (!$tokenList->hasKeywords(...explode(' ', $keyword))) {
                    continue;
                }
                switch ($type) {
                    case null:
                        $arguments[] = new KeywordLiteral($keyword);
                        continue 3;
                    case ExpressionNode::class:
                        $arguments[$keyword] = $this->parseExpression($tokenList);
                        continue 3;
                    case Charset::class:
                        $arguments[$keyword] = $tokenList->expectNameOrStringEnum(Charset::class);
                        continue 3;
                    case DataType::class:
                        $arguments[$keyword] = $this->parserFactory->getTypeParser()->parseType($tokenList);
                        continue 3;
                    case OrderByExpression::class:
                        $arguments[$keyword] = new ListExpression($this->parseOrderBy($tokenList));
                        continue 3;
                    case Literal::class:
                        $arguments[$keyword] = $this->parseLiteral($tokenList);
                        continue 3;
                    default:
                        throw new ShouldNotHappenException('Unsupported named parameter type.');
                }
            }

            if (!$first) {
                $tokenList->expect(TokenType::COMMA);
            }
            $arguments[] = $this->parseExpression($tokenList);
            $first = false;
        } while (true);

        if ($tokenList->hasKeyword(Keyword::OVER)) {
            $over = $this->parseOver($tokenList);
            // todo: parse AGG_FUNC(...) [over_clause]
        }

        return new FunctionCall($function, $arguments);
    }

    /**
     * TRIM([{BOTH | LEADING | TRAILING} [remstr] FROM] str), TRIM([remstr FROM] str)
     */
    private function parseTrim(TokenList $tokenList, BuiltInFunction $function): FunctionCall
    {
        $arguments = [];
        $keyword = $tokenList->getAnyKeyword(Keyword::LEADING, Keyword::TRAILING, Keyword::BOTH);
        if ($keyword !== null) {
            if ($tokenList->hasKeyword(Keyword::FROM)) {
                // TRIM(FOO FROM str)
                $second = $this->parseExpression($tokenList);
                $arguments[$keyword] = $second;
            } else {
                // TRIM(FOO remstr FROM str)
                $arguments[$keyword] = $this->parseExpression($tokenList);
                $tokenList->expectKeyword(Keyword::FROM);
                $arguments[] = $this->parseExpression($tokenList);
            }
        } else {
            $first = $this->parseExpression($tokenList);
            if ($tokenList->hasKeyword(Keyword::FROM)) {
                // TRIM(remstr FROM str)
                $arguments[Keyword::FROM] = $first;
                $arguments[] = $this->parseExpression($tokenList);
            } else {
                // TRIM(str)
                $arguments[] = $first;
            }
        }

        $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

        return new FunctionCall($function, $arguments);
    }

    /**
     * JSON_TABLE(expr, path COLUMNS column_list)  AS alias
     *
     * column_list:
     *   column[, column][, ...]
     *
     * column:
     *   name FOR ORDINALITY
     *   |  name type PATH string path [on_empty] [on_error]
     *   |  name type EXISTS PATH string path
     *   |  NESTED [PATH] path COLUMNS (column_list)
     *
     * on_empty:
     *   {NULL | DEFAULT json_string | ERROR} ON EMPTY
     *
     * on_error:
     *   {NULL | DEFAULT json_string | ERROR} ON ERROR
     */
    private function parseJsonTable(TokenList $tokenList, BuiltInFunction $function): FunctionCall
    {
        // todo:
    }

    /**
     * over_clause:
     *   {OVER (window_spec) | OVER window_name}
     *
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
     *
     * frame_start, frame_end: {
     *     CURRENT ROW
     *   | UNBOUNDED PRECEDING
     *   | UNBOUNDED FOLLOWING
     *   | expr PRECEDING
     *   | expr FOLLOWING
     * }
     */
    private function parseOver(TokenList $tokenList): int
    {
        // todo:
    }

    /**
     * CASE value WHEN [compare_value] THEN result [WHEN [compare_value] THEN result ...] [ELSE result] END
     *
     * CASE WHEN [condition] THEN result [WHEN [condition] THEN result ...] [ELSE result] END
     */
    private function parseCase(TokenList $tokenList): CaseExpression
    {
        $condition = null;
        if (!$tokenList->hasKeyword(Keyword::WHEN)) {
            $condition = $this->parseExpression($tokenList);
            $tokenList->expectKeyword(Keyword::WHEN);
        }
        $values = $results = [];
        do {
            $values[] = $this->parseExpression($tokenList);
            $tokenList->expectKeyword(Keyword::THEN);
            $results[] = $this->parseExpression($tokenList);
        } while ($tokenList->hasKeyword(Keyword::WHEN));

        if ($tokenList->hasKeyword(Keyword::ELSE)) {
            $results[] = $this->parseExpression($tokenList);
        }

        $tokenList->expectKeywords(Keyword::END);

        return new CaseExpression($condition, $values, $results);
    }

    /**
     * MATCH (col1, col2, ...) AGAINST (expr [search_modifier])
     *
     * search_modifier:
     *     IN NATURAL LANGUAGE MODE
     *   | IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION
     *   | IN BOOLEAN MODE
     *   | WITH QUERY EXPANSION
     */
    private function parseMatch(TokenList $tokenList): MatchExpression
    {
        $tokenList->expect(TokenType::LEFT_PARENTHESIS);
        $columns = [];
        do {
            $columns[] = $this->parseColumnName($tokenList);
        } while ($tokenList->hasComma());
        $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

        $tokenList->expectKeyword(Keyword::AGAINST);
        $tokenList->expect(TokenType::LEFT_PARENTHESIS);
        $query = $tokenList->expectString();
        /** @var MatchMode|null $mode */
        $mode = $tokenList->getKeywordEnum(MatchMode::class);
        $expansion = $tokenList->hasKeywords(Keyword::WITH, Keyword::QUERY, Keyword::EXPANSION);
        $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

        return new MatchExpression($columns, $query, $mode, $expansion);
    }

    public function parseColumnName(TokenList $tokenList): ColumnName
    {
        $first = $tokenList->expectName();
        if ($tokenList->has(TokenType::DOT)) {
            // a reserved keyword may follow after "." unescaped as we know it is a name context
            $secondToken = $tokenList->get(TokenType::KEYWORD);
            if ($secondToken !== null) {
                /** @var string $second */
                $second = $secondToken->value;
            } else {
                $second = $tokenList->expectName();
            }
            if ($tokenList->has(TokenType::DOT)) {
                // a reserved keyword may follow after "." unescaped as we know it is a name context
                $thirdToken = $tokenList->get(TokenType::KEYWORD);
                if ($thirdToken !== null) {
                    /** @var string $third */
                    $third = $thirdToken->value;
                } else {
                    $third = $tokenList->expectName();
                }

                return new ColumnName($third, $second, $first);
            }

            return new ColumnName($second, $first, null);
        }

        return new ColumnName($first, null, null);
    }

    private function parseSubquery(TokenList $tokenList): Subquery
    {
        return new Subquery($this->parserFactory->getQueryParser()->parseQuery($tokenList));
    }

    private function parseLiteral(TokenList $tokenList): Literal
    {
        $literal = $this->parseLiteralValue($tokenList);

        return $literal instanceof Literal ? $literal : new ValueLiteral($literal);
    }

    /**
     * @return string|int|float|bool|Literal
     */
    public function parseLiteralValue(TokenList $tokenList)
    {
        $token = $tokenList->expect(TokenType::VALUE);

        if (($token->type & TokenType::BINARY_LITERAL) !== 0) {
            /** @var string $value */
            $value = $token->value;

            return new BinaryLiteral($value);
        } elseif (($token->type & TokenType::HEXADECIMAL_LITERAL) !== 0) {
            /** @var string $value */
            $value = $token->value;

            return new HexadecimalLiteral($value);
        } elseif (($token->type & TokenType::KEYWORD) !== 0) {
            if ($token->value === Keyword::NULL) {
                return new KeywordLiteral(Keyword::NULL);
            } elseif ($token->value === Keyword::TRUE) {
                return true;
            } elseif ($token->value === Keyword::FALSE) {
                return false;
            } elseif ($token->value === Keyword::DEFAULT) {
                return new KeywordLiteral(Keyword::DEFAULT);
            } elseif ($token->value === Keyword::ON || $token->value === Keyword::OFF) {
                return new KeywordLiteral($token->value);
            } else {
                $tokenList->expectedAnyKeyword(Keyword::NULL, Keyword::TRUE, Keyword::FALSE, Keyword::DEFAULT, Keyword::ON, Keyword::OFF);
            }
        } else {
            /** @var string|int|float $value */
            $value = $token->value;

            return $value;
        }
    }

    /**
     * order_by:
     *     [ORDER BY {col_name | expr | position} [ASC | DESC], ...]
     *
     * @return OrderByExpression[]
     */
    public function parseOrderBy(TokenList $tokenList): array
    {
        $orderBy = [];
        do {
            $column = $position = $collation = null;
            $expression = $this->parseExpression($tokenList);

            // transform to more detailed shape
            if ($expression instanceof CollateExpression) {
                $collation = $expression->getCollation();
                $expression = $expression->getExpression();
            }
            // extract column name or position
            if ($expression instanceof Literal) {
                $value = $expression->getValue();
                if (is_int($value) || $value === (string) (int) $value) {
                    $position = (int) $value;
                    $expression = null;
                }
            } elseif ($expression instanceof Identifier) {
                $column = $expression->getName();
                $expression = null;
            }

            /** @var Order $order */
            $order = $tokenList->getKeywordEnum(Order::class);

            if ($collation === null && $tokenList->hasKeyword(Keyword::COLLATE)) {
                $collation = $tokenList->expectNameOrStringEnum(Collation::class);
            }

            $orderBy[] = new OrderByExpression($order, $column, $expression, $position, $collation);
        } while ($tokenList->hasComma());

        return $orderBy;
    }

    /**
     * limit:
     *     [LIMIT {[offset,] row_count | row_count OFFSET offset}]
     *
     * @return int[]|null[]|array{int, int|null} ($limit, $offset)
     */
    public function parseLimitAndOffset(TokenList $tokenList): array
    {
        $limit = $tokenList->expectInt();
        $offset = null;
        if ($tokenList->hasKeyword(Keyword::OFFSET)) {
            $offset = $tokenList->expectInt();
        } elseif ($tokenList->hasComma()) {
            $offset = $limit;
            $limit = $tokenList->expectInt();
        }

        return [$limit, $offset];
    }

    /**
     * expression:
     *     timestamp [+ INTERVAL interval] ...
     */
    public function parseTimeExpression(TokenList $tokenList): TimeExpression
    {
        $time = $this->parseDateTime($tokenList);
        $intervals = [];
        while ($tokenList->hasOperator(Operator::PLUS)) {
            $tokenList->expectKeyword(Keyword::INTERVAL);
            $intervals[] = $this->parseInterval($tokenList);
        }

        return new TimeExpression($time, $intervals);
    }

    public function parseDateTime(TokenList $tokenList): DateTime
    {
        $string = (string) $tokenList->getInt();
        if ($string === '') {
            $string = $tokenList->expectString();
        }
        if (Re::match($string, self::INT_DATETIME_EXPRESSION) !== null) {
            if (strlen($string) === 12) {
                $string = '20' . $string;
            }

            return new DateTime(sprintf(
                '%s-%s-%s %s:%s:%s',
                substr($string, 0, 4),
                substr($string, 4, 2),
                substr($string, 6, 2),
                substr($string, 8, 2),
                substr($string, 10, 2),
                substr($string, 12, 2)
            ));
            // phpcs:ignore SlevomatCodingStandard.ControlStructures.AssignmentInCondition.AssignmentInCondition
        } elseif (($match = Re::match($string, self::STRING_DATETIME_EXPRESSION)) !== null) {
            $string = $match[1];
            $decimalPart = $match[2] ?? '';
            if (strlen($string) === 17) {
                $string = '20' . $string;
            }

            return new DateTime(sprintf(
                '%s-%s-%s %s:%s:%s%s',
                substr($string, 0, 4),
                substr($string, 5, 2),
                substr($string, 8, 2),
                substr($string, 11, 2),
                substr($string, 14, 2),
                substr($string, 17, 2),
                $decimalPart
            ));
        } else {
            throw new ParserException("Invalid datetime value \"$string\"");
        }
    }

    /**
     * interval:
     *     quantity {YEAR | QUARTER | MONTH | DAY | HOUR | MINUTE |
     *          WEEK | SECOND | YEAR_MONTH | DAY_HOUR | DAY_MINUTE |
     *          DAY_SECOND | HOUR_MINUTE | HOUR_SECOND | MINUTE_SECOND}
     */
    public function parseInterval(TokenList $tokenList): TimeInterval
    {
        $value = $this->parseExpression($tokenList);

        /** @var TimeIntervalUnit $unit */
        $unit = $tokenList->expectKeywordEnum(TimeIntervalUnit::class);

        return new TimeInterval($value, $unit);
    }

    public function parseUserExpression(TokenList $tokenList): UserExpression
    {
        if ($tokenList->hasKeyword(Keyword::CURRENT_USER)) {
            $tokenList->passParens(); // CURRENT_USER()

            return new UserExpression(null, Keyword::CURRENT_USER);
        } else {
            return new UserExpression(new UserName(...$tokenList->expectUserName()));
        }
    }

}
