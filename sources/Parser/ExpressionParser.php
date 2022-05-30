<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable SlevomatCodingStandard.ControlStructures.AssignmentInCondition

namespace SqlFtw\Parser;

use Dogma\Re;
use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use Dogma\Time\DateTime;
use SqlFtw\Parser\Dml\QueryParser;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\Dml\FileFormat;
use SqlFtw\Sql\Dml\Query\WindowSpecification;
use SqlFtw\Sql\Expression\AllLiteral;
use SqlFtw\Sql\Expression\AssignOperator;
use SqlFtw\Sql\Expression\Asterisk;
use SqlFtw\Sql\Expression\BaseType;
use SqlFtw\Sql\Expression\BinaryLiteral;
use SqlFtw\Sql\Expression\BinaryOperator;
use SqlFtw\Sql\Expression\BoolLiteral;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\CaseExpression;
use SqlFtw\Sql\Expression\CastType;
use SqlFtw\Sql\Expression\CollateExpression;
use SqlFtw\Sql\Expression\ColumnIdentifier;
use SqlFtw\Sql\Expression\ColumnName;
use SqlFtw\Sql\Expression\ColumnType;
use SqlFtw\Sql\Expression\CurlyExpression;
use SqlFtw\Sql\Expression\DateLiteral;
use SqlFtw\Sql\Expression\DatetimeLiteral;
use SqlFtw\Sql\Expression\DefaultLiteral;
use SqlFtw\Sql\Expression\ExistsExpression;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\IntervalLiteral;
use SqlFtw\Sql\Expression\IntLiteral;
use SqlFtw\Sql\Expression\JsonErrorCondition;
use SqlFtw\Sql\Expression\JsonTableExistsPathColumn;
use SqlFtw\Sql\Expression\JsonTableNestedColumns;
use SqlFtw\Sql\Expression\JsonTableOrdinalityColumn;
use SqlFtw\Sql\Expression\JsonTablePathColumn;
use SqlFtw\Sql\Expression\ListExpression;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\MatchExpression;
use SqlFtw\Sql\Expression\MatchMode;
use SqlFtw\Sql\Expression\MaxValueLiteral;
use SqlFtw\Sql\Expression\NoneLiteral;
use SqlFtw\Sql\Expression\NullLiteral;
use SqlFtw\Sql\Expression\NumberLiteral;
use SqlFtw\Sql\Expression\OnOffLiteral;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Expression\Parentheses;
use SqlFtw\Sql\Expression\Placeholder;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\Expression\RowExpression;
use SqlFtw\Sql\Expression\Scope;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Expression\Subquery;
use SqlFtw\Sql\Expression\SystemVariable;
use SqlFtw\Sql\Expression\TernaryOperator;
use SqlFtw\Sql\Expression\TimeExpression;
use SqlFtw\Sql\Expression\TimeInterval;
use SqlFtw\Sql\Expression\TimeIntervalUnit;
use SqlFtw\Sql\Expression\TimeLiteral;
use SqlFtw\Sql\Expression\TimeTypeLiteral;
use SqlFtw\Sql\Expression\TimeValue;
use SqlFtw\Sql\Expression\UintLiteral;
use SqlFtw\Sql\Expression\UnaryOperator;
use SqlFtw\Sql\Expression\UnknownLiteral;
use SqlFtw\Sql\Expression\UserVariable;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Order;
use SqlFtw\Sql\SqlMode;
use function explode;
use function in_array;
use function is_int;
use function sprintf;
use function strlen;
use function strtoupper;
use function substr;

class ExpressionParser
{
    use StrictBehaviorMixin;

    private const PUNCTUATION = '[~`@#$%^&\'"\\\\=[\\]{}()<>;:,.?!_|\\/*+-]';

    private const INT_DATETIME_EXPRESSION = '/^(?:[1-9][0-9])?[0-9]{2}(?:0[1-9]|1[012])(?:0[1-9]|[12][0-9]|3[01])(?:[01][0-9]|2[0-3])(?:[0-5][0-9]){2}$/';

    private const STRING_DATETIME_EXPRESSION = '/^'
        . '((?:[1-9][0-9])?[0-9]{2}' . self::PUNCTUATION . '(?:0[1-9]|1[012])' . self::PUNCTUATION . '(?:0[1-9]|[12][0-9]|3[01])' // date
        . '[ T](?:[01][0-9]|2[0-3])' . self::PUNCTUATION . '[0-5][0-9]' . self::PUNCTUATION . '[0-5][0-9])' // time
        . '(\\.[0-9]+)?$/'; // ms

    /** @var callable(): QueryParser */
    private $queryParserProxy;

    /** @var bool */
    private $assignAllowed = false;

    /**
     * @param callable(): QueryParser $queryParserProxy
     */
    public function __construct(callable $queryParserProxy)
    {
        $this->queryParserProxy = $queryParserProxy;
    }

    /**
     * assign_expr:
     *    [user_variable :=] expr
     */
    public function parseAssignExpression(TokenList $tokenList): ExpressionNode
    {
        $this->assignAllowed = true;
        try {
            return $this->parseExpression($tokenList);
        } finally {
            $this->assignAllowed = false;
        }
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
    public function parseExpression(TokenList $tokenList): RootNode
    {
        $operators = [Operator::OR, Operator::XOR, Operator::AND, Operator::AMPERSANDS];
        if (!$tokenList->getSettings()->getMode()->containsAny(SqlMode::PIPES_AS_CONCAT)) {
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
            $keyword = strtoupper($tokenList->expectAnyKeyword(Keyword::TRUE, Keyword::FALSE, Keyword::UNKNOWN));
            $right = $keyword === Keyword::UNKNOWN
                ? new UnknownLiteral()
                : new BoolLiteral($keyword === Keyword::TRUE);

            return new BinaryOperator($left, $not ? [Operator::IS, Operator::NOT] : [Operator::IS], $right);
        } elseif ($this->assignAllowed && $left instanceof UserVariable && $tokenList->hasOperator(Operator::ASSIGN)) {
            $right = $this->parseExpression($tokenList);

            return new AssignOperator($left, $right);
        } else {
            return $left;
        }
    }

    /**
     * @return non-empty-array<RootNode>
     */
    private function parseExpressionList(TokenList $tokenList): array
    {
        $expressions = [];
        do {
            $expressions[] = $this->parseExpression($tokenList);
        } while ($tokenList->hasSymbol(','));

        return $expressions;
    }

    /**
     * boolean_primary:
     *     boolean_primary IS [NOT] [NULL|TRUE|FALSE]
     *   | boolean_primary <=> predicate
     *   | boolean_primary comparison_operator predicate
     *   | boolean_primary comparison_operator {ALL | ANY | SOME} (subquery)
     *   | predicate
     *
     * comparison_operator: = | >= | > | <= | < | <> | !=
     */
    private function parseBooleanPrimary(TokenList $tokenList): RootNode
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
            $quantifier = $tokenList->getAnyKeyword(Keyword::ALL, Keyword::ANY, Keyword::SOME);
            if ($quantifier !== null) {
                $tokenList->expectSymbol('(');
                $subquery = new Parentheses($this->parseSubquery($tokenList));
                $tokenList->expectSymbol(')');

                return new BinaryOperator($left, [$operator, $quantifier], $subquery);
            } else {
                $right = $this->parsePredicate($tokenList);

                return new BinaryOperator($left, [$operator], $right);
            }
        } elseif ($tokenList->hasKeyword(Keyword::IS)) {
            $not = $tokenList->hasKeyword(Keyword::NOT);
            $keyword = $tokenList->expectAnyKeyword(Keyword::NULL, Keyword::TRUE, Keyword::FALSE, Keyword::UNKNOWN);
            switch ($keyword) {
                case Keyword::TRUE:
                    $right = new BoolLiteral(true);
                    break;
                case Keyword::FALSE:
                    $right = new BoolLiteral(false);
                    break;
                case Keyword::NULL:
                    $right = new NullLiteral();
                    break;
                default:
                    $right = new UnknownLiteral();
                    break;
            }

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
     *   | bit_expr MEMBER [OF] (json_array) // 8.0.17
     *   | bit_expr
     */
    private function parsePredicate(TokenList $tokenList): RootNode
    {
        $left = $this->parseBitExpression($tokenList);
        if ($tokenList->hasKeywords(Keyword::SOUNDS, Keyword::LIKE)) {
            $right = $this->parseBitExpression($tokenList);

            return new BinaryOperator($left, [Operator::SOUNDS, Operator::LIKE], $right);
        }

        $not = $tokenList->hasKeyword(Keyword::NOT);

        $position = $tokenList->getPosition();
        if ($tokenList->hasKeyword(Keyword::IN)) {
            // lonely IN can be a named parameter "POSITION(substr IN str)"
            if (!$not && !$tokenList->hasSymbol('(')) {
                $tokenList->resetPosition($position);

                return $left;
            }

            $tokenList->resetPosition($position);
            $tokenList->expectKeyword(Keyword::IN);
            $tokenList->expectSymbol('(');
            if ($tokenList->hasAnyKeyword(Keyword::SELECT, Keyword::TABLE, Keyword::VALUES, Keyword::WITH)) {
                $subquery = new Parentheses($this->parseSubquery($tokenList->resetPosition(-1)));
                $tokenList->expectSymbol(')');

                return new BinaryOperator($left, $not ? [Operator::NOT, Operator::IN] : [Operator::IN], $subquery);
            } else {
                $expressions = new Parentheses(new ListExpression($this->parseExpressionList($tokenList)));
                $tokenList->expectSymbol(')');

                return new BinaryOperator($left, $not ? [Operator::NOT, Operator::IN] : [Operator::IN], $expressions);
            }
        }

        if ($tokenList->hasKeyword(Keyword::BETWEEN)) {
            $middle = $this->parseBitExpression($tokenList);
            $tokenList->expectKeyword(Keyword::AND);
            $right = $this->parseBitExpression($tokenList);

            return new TernaryOperator($left, $not ? [Operator::NOT, Operator::BETWEEN] : [Operator::BETWEEN], $middle, Operator::AND, $right);
        }

        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $second = $this->parseSimpleExpression($tokenList);
            if ($tokenList->hasKeyword(Keyword::ESCAPE)) {
                $third = $this->parseSimpleExpression($tokenList);

                return new TernaryOperator($left, $not ? [Operator::NOT, Operator::LIKE] : [Operator::LIKE], $second, Operator::ESCAPE, $third);
            } else {
                return new BinaryOperator($left, $not ? [Operator::NOT, Operator::LIKE] : [Operator::LIKE], $second);
            }
        }

        $operator = $tokenList->getAnyKeyword(Keyword::REGEXP, Keyword::RLIKE);
        if ($operator !== null) {
            $right = $this->parseBitExpression($tokenList);

            return new BinaryOperator($left, $not ? [Operator::NOT, $operator] : [$operator], $right);
        }

        if (!$not && $tokenList->hasKeyword(Keyword::MEMBER)) {
            $tokenList->passKeyword(Keyword::OF);
            $right = $this->parseBitExpression($tokenList);

            return new BinaryOperator($left, [Operator::MEMBER_OF], $right);
        }

        return $left;
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
     *   | bit_expr -> json_path
     *   | bit_expr ->> json_path
     *   | simple_expr
     */
    private function parseBitExpression(TokenList $tokenList): RootNode
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
            Operator::MODULO,
            Operator::BIT_XOR,
            Operator::JSON_EXTRACT,
            Operator::JSON_EXTRACT_UNQUOTE,
        ];

        $left = $this->parseSimpleExpression($tokenList);
        $operator = $tokenList->getAnyOperator(...$operators);
        if ($operator === null) {
            $operator = $tokenList->getAnyKeyword(Keyword::DIV, Keyword::MOD);
        }
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
    private function parseSimpleExpression(TokenList $tokenList): RootNode
    {
        $left = $this->parseSimpleExpressionLeft($tokenList);

        if ($tokenList->hasKeyword(Keyword::COLLATE)) {
            // simple_expr COLLATE collation_name
            $collation = $tokenList->expectCollationName();

            return new CollateExpression($left, $collation);
        }

        if ($tokenList->getSettings()->getMode()->containsAny(SqlMode::PIPES_AS_CONCAT) && $tokenList->hasOperator(Operator::PIPES)) {
            // simple_expr || simple_expr
            $right = $this->parseSimpleExpression($tokenList);

            return new BinaryOperator($left, Operator::PIPES, $right);
        }

        return $left;
    }

    private function parseSimpleExpressionLeft(TokenList $tokenList): RootNode
    {
        $operator = $tokenList->getAnyOperator(Operator::PLUS, Operator::MINUS, Operator::BIT_INVERT, Operator::EXCLAMATION, Operator::BINARY);
        if ($operator !== null) {
            // + simple_expr
            // - simple_expr
            // ~ simple_expr
            // ! simple_expr
            // BINARY simple_expr
            if ($operator === Operator::BINARY && $tokenList->isFinished() || $tokenList->hasSymbol(';') || $tokenList->hasSymbol(',')) {
                return new SimpleName(Charset::BINARY);
            } else {
                return new UnaryOperator($operator, $this->parseSimpleExpression($tokenList));
            }
        } elseif ($tokenList->hasKeyword(Keyword::EXISTS)) {
            // EXISTS (subquery)
            $tokenList->expectSymbol('(');
            $subquery = $this->parseSubquery($tokenList);
            $tokenList->expectSymbol(')');

            return new ExistsExpression($subquery);
        } elseif ($tokenList->hasSymbol('(')) {
            if ($tokenList->hasAnyKeyword(Keyword::SELECT, Keyword::TABLE, Keyword::VALUES, Keyword::WITH)) {
                // (subquery)
                $subquery = $this->parseSubquery($tokenList->resetPosition(-1));
                $tokenList->expectSymbol(')');

                return new Parentheses($subquery);
            } else {
                // (expr [, expr] ...)
                $expressions = $this->parseExpressionList($tokenList);
                $tokenList->expectSymbol(')');

                return new Parentheses(new ListExpression($expressions));
            }
        } elseif ($tokenList->hasKeyword(Keyword::ROW)) {
            if ($tokenList->hasSymbol('(')) {
                // ROW (expr, expr [, expr] ...)
                $expressions = $this->parseExpressionList($tokenList);
                $tokenList->expectSymbol(')');

                return new RowExpression($expressions);
            } else {
                // e.g. SET @@session.binlog_format = ROW;
                // todo: in fact a value
                return new SimpleName(Keyword::ROW);
            }
        } elseif ($tokenList->hasKeyword(Keyword::INTERVAL)) {
            // interval_expr
            return new IntervalLiteral($this->parseInterval($tokenList));
        } elseif ($tokenList->hasKeyword(Keyword::CASE)) {
            // case_expr
            return $this->parseCase($tokenList);
        } elseif ($tokenList->hasKeyword(Keyword::MATCH)) {
            // match_expr
            return $this->parseMatch($tokenList);
        } elseif ($tokenList->has(TokenType::PLACEHOLDER)) {
            // param_marker
            return new Placeholder();
        } elseif ($tokenList->hasSymbol('{')) {
            // {identifier expr}
            // @see https://docs.microsoft.com/en-us/sql/odbc/reference/develop-app/escape-sequences-in-odbc?view=sql-server-ver16
            $name = $tokenList->expectName();
            $expression = $this->parseExpression($tokenList);
            $tokenList->expectSymbol('}');

            return new CurlyExpression($name, $expression);
        }

        $variable = $tokenList->get(TokenType::AT_VARIABLE);
        if ($variable !== null) {
            // @variable
            return $this->parseAtVariable($tokenList, $variable->value);
        }

        // may be preceded by charset introducer, e.g. _utf8
        $string = $tokenList->getStringValue();
        if ($string !== null) {
            return $string;
        }

        // {DATE | TIME | DATETIME | TIMESTAMP} literal
        $value = $this->parseTimeValue($tokenList);
        if ($value !== null) {
            return $value;
        }

        $name1 = $tokenList->getName();
        if ($name1 !== null) {
            $name2 = $name3 = null;
            if ($tokenList->hasSymbol('.')) {
                if ($tokenList->hasOperator(Operator::MULTIPLY)) {
                    $name2 = '*'; // tbl.*
                } else {
                    $name2 = $tokenList->expectName();
                }
                if ($name2 !== '*' && $tokenList->hasSymbol('.')) {
                    if ($tokenList->hasOperator(Operator::MULTIPLY)) {
                        $name3 = '*'; // db.tbl.*
                    } else {
                        $name3 = $tokenList->expectName();
                    }
                }
            }
            $platformFeatures = $tokenList->getSettings()->getPlatform()->getFeatures();
            if ($name3 !== null) {
                // identifier
                return new ColumnName($name3, $name2, $name1);
            } elseif ($tokenList->hasSymbol('(')) {
                // function_call
                return $this->parseFunctionCall($tokenList, $name1, $name2);
            } elseif ($name2 !== null) {
                // identifier
                return new QualifiedName($name2, $name1);
            } elseif (BuiltInFunction::isValid($name1) && $platformFeatures->isReserved($name1)) {
                // function without parentheses
                $function = BuiltInFunction::get($name1);
                if ($name1 === Keyword::DEFAULT) {
                    return new DefaultLiteral();
                } elseif (!$function->isBare()) {
                    // throws
                    $tokenList->expectSymbol('(');
                    $tokenList->expectSymbol(')');
                }

                return new FunctionCall($function);
            } else {
                // identifier
                return new SimpleName($name1);
            }
        }

        // literal
        return $this->parseLiteral($tokenList);
    }

    /**
     * @return SystemVariable|UserVariable
     */
    public function parseAtVariable(TokenList $tokenList, string $atVariable): Identifier
    {
        if (in_array(strtoupper($atVariable), ['@@LOCAL', '@@SESSION', '@@GLOBAL', '@@PERSIST', '@@PERSIST_ONLY'], true)) {
            // @@global.foo
            $tokenList->expectSymbol('.');
            if (strtoupper($atVariable) === '@@LOCAL') {
                $atVariable = '@@SESSION';
            }
            $scope = Scope::get(substr($atVariable, 2));

            $name = $tokenList->expectName();
            if ($tokenList->hasSymbol('.')) {
                $name .= '.' . $tokenList->expectName();
            }

            return new SystemVariable($name, $scope);
        } elseif (substr($atVariable, 0, 2) === '@@') {
            // @@foo
            $name = substr($atVariable, 2);
            if ($tokenList->hasSymbol('.')) {
                $name .= '.' . $tokenList->expectName();
            }

            return new SystemVariable($name);
        } else {
            // @foo
            return new UserVariable($atVariable);
        }
    }

    private function parseFunctionCall(TokenList $tokenList, string $name1, ?string $name2 = null): FunctionCall
    {
        $function = $name2 === null && BuiltInFunction::validateValue($name1)
            ? BuiltInFunction::get($name1)
            : new QualifiedName($name2 ?? $name1, $name2 !== null ? $name1 : null);

        if ($tokenList->hasSymbol(')')) {
            return new FunctionCall($function, []);
        }

        if ($function instanceof BuiltInFunction) {
            $name = $function->getValue();
            if ($name === BuiltInFunction::COUNT) {
                if ($tokenList->hasOperator(Operator::MULTIPLY)) {
                    $tokenList->expectSymbol(')');

                    return new FunctionCall($function, [new Asterisk()]);
                }
            } elseif ($name === BuiltInFunction::TRIM) {
                return $this->parseTrim($tokenList, $function);
            } elseif ($name === BuiltInFunction::JSON_VALUE) {
                return $this->parseJsonValue($tokenList, $function);
            } elseif ($name === BuiltInFunction::JSON_TABLE) {
                return $this->parseJsonTable($tokenList, false);
            }
            $namedParams = $function->getNamedParams();
        } else {
            $namedParams = [];
        }

        $arguments = [];
        $first = true;
        do {
            if ($tokenList->hasSymbol(')')) {
                break;
            }
            foreach ($namedParams as $keyword => $type) {
                if (!$tokenList->hasKeywords(...explode(' ', $keyword))) {
                    continue;
                }
                switch ($type) {
                    case ExpressionNode::class:
                        $arguments[$keyword] = $this->parseExpression($tokenList);
                        continue 3;
                    case Charset::class:
                        $arguments[$keyword] = $tokenList->expectCharsetName();
                        continue 3;
                    case CastType::class:
                        $arguments[$keyword] = $this->parseCastType($tokenList);
                        continue 3;
                    case OrderByExpression::class:
                        $arguments[$keyword] = new ListExpression($this->parseOrderBy($tokenList));
                        continue 3;
                    case Literal::class:
                        $arguments[$keyword] = $this->parseLiteral($tokenList);
                        continue 3;
                    case null:
                        if (in_array($keyword, [Keyword::DATE, Keyword::TIME, Keyword::DATETIME], true)) {
                            $arguments[] = new TimeTypeLiteral($keyword);
                            continue 3;
                        }
                    default:
                        throw new ShouldNotHappenException('Unsupported named parameter type.');
                }
            }

            if (!$first) {
                $tokenList->expectSymbol(',');
            }
            $arguments[] = $this->parseExpression($tokenList);
            $first = false;
        } while (true);

        $over = null;
        if ($function instanceof BuiltInFunction && $function->isWindow() && $tokenList->hasKeyword(Keyword::OVER)) {
            // AGG_FUNC(...) [over_clause]
            $over = $this->parseOver($tokenList);
        }

        return new FunctionCall($function, $arguments, $over);
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

        $tokenList->expectSymbol(')');

        return new FunctionCall($function, $arguments);
    }

    /**
     * JSON_VALUE(json_doc, path [RETURNING type] [on_empty] [on_error])
     *
     * on_empty:
     *     {NULL | ERROR | DEFAULT value} ON EMPTY
     *
     * on_error:
     *     {NULL | ERROR | DEFAULT value} ON ERROR
     */
    private function parseJsonValue(TokenList $tokenList, BuiltInFunction $function): FunctionCall
    {
        $params = [$this->parseExpression($tokenList)];
        $tokenList->expectSymbol(',');
        $params[] = $this->parseExpression($tokenList);

        if ($tokenList->hasKeyword(Keyword::RETURNING)) {
            $params[Keyword::RETURNING] = $this->parseCastType($tokenList);
        }

        [$onEmpty, $onError] = $this->parseOnEmptyOnError($tokenList);
        if ($onEmpty !== null) {
            $params[Keyword::ON . ' ' . Keyword::EMPTY] = $onEmpty;
        }
        if ($onError !== null) {
            $params[Keyword::ON . ' ' . Keyword::ERROR] = $onError;
        }

        $tokenList->expectSymbol(')');

        return new FunctionCall($function, $params);
    }

    /**
     * JSON_TABLE(expr, path COLUMNS (column_list)) [AS] alias
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
    public function parseJsonTable(TokenList $tokenList, bool $parseIntro = true): FunctionCall
    {
        if ($parseIntro) {
            $tokenList->expectKeyword(Keyword::JSON_TABLE);
            $tokenList->expectSymbol('(');
        }

        $expression = $this->parseExpression($tokenList);
        $tokenList->expectSymbol(',');
        $path = $tokenList->expectStringValue();

        $tokenList->expectKeyword(Keyword::COLUMNS);
        $columns = $this->parseJsonTableColumns($tokenList);

        $tokenList->expectSymbol(')');

        return new FunctionCall(BuiltInFunction::get(BuiltInFunction::JSON_TABLE), [$expression, $path, Keyword::COLUMNS => $columns]);
    }

    private function parseJsonTableColumns(TokenList $tokenList): Parentheses
    {
        $tokenList->expectSymbol('(');
        $columns = [];
        do {
            if ($tokenList->hasKeyword(Keyword::NESTED)) {
                $tokenList->passKeyword(Keyword::PATH);
                $path = $tokenList->expectStringValue();
                $tokenList->expectKeyword(Keyword::COLUMNS);
                $columns[] = new JsonTableNestedColumns($path, $this->parseJsonTableColumns($tokenList));
                continue;
            }

            $name = $tokenList->expectName();

            if ($tokenList->hasKeywords(Keyword::FOR, Keyword::ORDINALITY)) {
                $columns[] = new JsonTableOrdinalityColumn($name);
                continue;
            }

            $type = $this->parseColumnType($tokenList);
            $keyword = $tokenList->expectAnyKeyword(Keyword::PATH, Keyword::EXISTS);
            if ($keyword === Keyword::PATH) {
                $path = $tokenList->expectStringValue();
                [$onEmpty, $onError] = $this->parseOnEmptyOnError($tokenList);

                $columns[] = new JsonTablePathColumn($name, $type, $path, $onEmpty, $onError);
            } else {
                $tokenList->expectKeyword(Keyword::PATH);
                $path = $tokenList->expectStringValue();

                $columns[] = new JsonTableExistsPathColumn($name, $type, $path);
            }
        } while ($tokenList->hasSymbol(','));

        $tokenList->expectSymbol(')');

        return new Parentheses(new ListExpression($columns));
    }

    /**
     * @return array{JsonErrorCondition|null, JsonErrorCondition|null}
     */
    private function parseOnEmptyOnError(TokenList $tokenList): array
    {
        $onEmpty = $onError = null;
        while (($keyword = $tokenList->getAnyKeyword(Keyword::NULL, Keyword::ERROR, Keyword::DEFAULT)) !== null) {
            if ($keyword === Keyword::NULL) {
                $default = true;
            } elseif ($keyword === Keyword::ERROR) {
                $default = false;
            } else {
                $default = $this->parseLiteral($tokenList);
            }
            $tokenList->expectKeyword(Keyword::ON);
            $event = $tokenList->expectAnyKeyword(Keyword::EMPTY, Keyword::ERROR);
            if ($event === Keyword::EMPTY) {
                if (isset($onEmpty)) {
                    throw new ParserException('ON EMPTY defined twice in JSON_TABLE', $tokenList);
                }
                $onEmpty = new JsonErrorCondition($default);
            } else {
                if (isset($onError)) {
                    throw new ParserException('ON ERROR defined twice in JSON_TABLE', $tokenList);
                }
                $onError = new JsonErrorCondition($default);
            }
        }

        return [$onEmpty, $onError];
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
     *
     * @return WindowSpecification|string
     */
    private function parseOver(TokenList $tokenList)
    {
        if ($tokenList->hasSymbol('(')) {
            /** @var QueryParser $queryParser */
            $queryParser = ($this->queryParserProxy)();
            $window = $queryParser->parseWindow($tokenList);
            $tokenList->expectSymbol(')');

            return $window;
        } else {
            return $tokenList->expectNonReservedNameOrString();
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
     * MATCH {col1, col2, ...|(col1, col2, ...)} AGAINST (expr [search_modifier])
     *
     * search_modifier:
     *     IN NATURAL LANGUAGE MODE
     *   | IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION
     *   | IN BOOLEAN MODE
     *   | WITH QUERY EXPANSION
     */
    private function parseMatch(TokenList $tokenList): MatchExpression
    {
        $columns = [];
        if ($tokenList->hasSymbol('(')) {
            do {
                $columns[] = $this->parseColumnName($tokenList);
            } while ($tokenList->hasSymbol(','));
            $tokenList->expectSymbol(')');
        } else {
            do {
                $columns[] = $this->parseColumnName($tokenList);
            } while ($tokenList->hasSymbol(','));
        }

        $tokenList->expectKeyword(Keyword::AGAINST);
        $tokenList->expectSymbol('(');
        $query = $this->parseExpression($tokenList);
        $mode = null;
        if ($tokenList->hasKeyword(Keyword::IN)) {
            $mode = $tokenList->expectMultiKeywordsEnum(MatchMode::class);
        }
        $expansion = $tokenList->hasKeywords(Keyword::WITH, Keyword::QUERY, Keyword::EXPANSION);
        $tokenList->expectSymbol(')');

        return new MatchExpression($columns, $query, $mode, $expansion);
    }

    public function parseColumnName(TokenList $tokenList): ColumnName
    {
        $first = $tokenList->expectName();
        if ($tokenList->hasSymbol('.')) {
            $second = $tokenList->expectName();
            if ($tokenList->hasSymbol('.')) {
                $third = $tokenList->expectName();

                return new ColumnName($third, $second, $first);
            }

            return new ColumnName($second, $first, null);
        }

        return new ColumnName($first, null, null);
    }

    private function parseSubquery(TokenList $tokenList): Subquery
    {
        /** @var QueryParser $queryParser */
        $queryParser = ($this->queryParserProxy)();

        return new Subquery($queryParser->parseQuery($tokenList));
    }

    public function parseLiteral(TokenList $tokenList): Literal
    {
        // StringLiteral | HexadecimalLiteral
        $value = $tokenList->getStringValue();
        if ($value !== null) {
            return $value;
        }

        $value = $this->parseTimeValue($tokenList);
        if ($value !== null) {
            return $value;
        }

        $token = $tokenList->expect(TokenType::VALUE | TokenType::KEYWORD);

        if (($token->type & TokenType::KEYWORD) !== 0) {
            $upper = strtoupper($token->value);
            if ($upper === Keyword::NULL) {
                return new NullLiteral();
            } elseif ($upper === Keyword::TRUE) {
                return new BoolLiteral(true);
            } elseif ($upper === Keyword::FALSE) {
                return new BoolLiteral(false);
            } elseif ($upper === Keyword::DEFAULT) {
                return new DefaultLiteral();
            } elseif ($upper === Keyword::ON || $upper === Keyword::OFF) {
                return new OnOffLiteral($upper === Keyword::ON);
            } elseif ($upper === Keyword::MAXVALUE) {
                return new MaxValueLiteral();
            } elseif ($upper === Keyword::ALL) {
                return new AllLiteral();
            } elseif ($upper === Keyword::NONE) {
                return new NoneLiteral();
            } else {
                $tokenList->missingAnyKeyword(Keyword::NULL, Keyword::TRUE, Keyword::FALSE, Keyword::DEFAULT, Keyword::ON, Keyword::OFF, Keyword::MAXVALUE, Keyword::ALL, Keyword::NONE);
            }
        } elseif (($token->type & TokenType::BINARY_LITERAL) !== 0) {
            return new BinaryLiteral($token->value);
        } elseif (($token->type & TokenType::UINT) !== 0) {
            return new UintLiteral($token->value);
        } elseif (($token->type & TokenType::INT) !== 0) {
            return new IntLiteral($token->value);
        } elseif (($token->type & TokenType::NUMBER) !== 0) {
            return new NumberLiteral($token->value);
        } else {
            throw new ShouldNotHappenException("Unknown token '$token->value' of type $token->type.");
        }
    }

    private function parseTimeValue(TokenList $tokenList): ?TimeValue
    {
        $position = $tokenList->getPosition();

        // {DATE | TIME | DATETIME | TIMESTAMP} literal
        $keyword = $tokenList->getAnyKeyword(Keyword::DATE, Keyword::TIME, Keyword::DATETIME, Keyword::TIMESTAMP);
        if ($keyword !== null) {
            $string = $tokenList->getString();
            if ($string !== null) {
                if ($keyword === Keyword::DATE) {
                    return new DateLiteral($string);
                } elseif ($keyword === Keyword::TIME) {
                    return new TimeLiteral($string);
                } else {
                    // todo: is TimestampLiteral needed?
                    return new DatetimeLiteral($string);
                }
            } else {
                $tokenList->resetPosition($position);
            }
        }

        return null;
    }

    /**
     * order_by:
     *     [ORDER BY {col_name | expr | position} [ASC | DESC], ...]
     *
     * @return non-empty-array<OrderByExpression>
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
            } elseif ($expression instanceof ColumnIdentifier) {
                $column = $expression;
                $expression = null;
            }

            /** @var Order $order */
            $order = $tokenList->getKeywordEnum(Order::class);

            if ($collation === null && $tokenList->hasKeyword(Keyword::COLLATE)) {
                $collation = $tokenList->expectCollationName();
            }

            $orderBy[] = new OrderByExpression($order, $column, $expression, $position, $collation);
        } while ($tokenList->hasSymbol(','));

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
        $limit = (int) $tokenList->expectUnsignedInt();
        $offset = null;
        if ($tokenList->hasKeyword(Keyword::OFFSET)) {
            $offset = (int) $tokenList->expectUnsignedInt();
        } elseif ($tokenList->hasSymbol(',')) {
            $offset = $limit;
            $limit = (int) $tokenList->expectUnsignedInt();
        }

        return [$limit, $offset];
    }

    /**
     * expression:
     *     timestamp [+ INTERVAL interval] ...
     */
    public function parseTimeExpression(TokenList $tokenList): TimeExpression
    {
        $initial = $this->parseDateTime($tokenList);
        $intervals = [];
        while ($tokenList->hasOperator(Operator::PLUS)) {
            $tokenList->expectKeyword(Keyword::INTERVAL);
            $intervals[] = $this->parseInterval($tokenList);
        }

        return new TimeExpression($initial, $intervals);
    }

    /**
     * @return DateTime|BuiltInFunction
     */
    public function parseDateTime(TokenList $tokenList)
    {
        if (($function = $tokenList->getAnyName(...BuiltInFunction::getTimeProviders())) !== null) {
            $function = BuiltInFunction::get($function);
            if (!$function->isBare()) {
                // throws
                $tokenList->expectSymbol('(');
                $tokenList->expectSymbol(')');
            } elseif ($tokenList->hasSymbol('(')) {
                $tokenList->expectSymbol(')');
            }

            return $function;
        }

        $string = (string) $tokenList->getUnsignedInt();
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
            throw new InvalidValueException("datetime", $tokenList);
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
            // CURRENT_USER()
            if ($tokenList->hasSymbol('(')) {
                $tokenList->expectSymbol(')');
            }

            return new UserExpression(BuiltInFunction::get(BuiltInFunction::CURRENT_USER));
        } else {
            return new UserExpression($tokenList->expectUserName());
        }
    }

    /**
     * data_type:
     *     BIT[(length)]
     *   | TINYINT[(length)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | SMALLINT[(length)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | MEDIUMINT[(length)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | INT[(length)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | INTEGER[(length)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | BIGINT[(length)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | REAL[(length,decimals)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | DOUBLE[(length,decimals)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | FLOAT[(length,decimals)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | FLOAT[(precision)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | DECIMAL[(length[,decimals])] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | NUMERIC[(length[,decimals])] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | DATE
     *   | TIME[(fsp)]
     *   | TIMESTAMP[(fsp)]
     *   | DATETIME[(fsp)]
     *   | YEAR
     *   | CHAR[(length)] [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | VARCHAR(length) [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | BINARY[(length)]
     *   | VARBINARY(length)
     *   | TINYBLOB
     *   | BLOB[(length)]
     *   | MEDIUMBLOB
     *   | LONGBLOB
     *   | TINYTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | TEXT[(length)] [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | MEDIUMTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | LONGTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | ENUM(value1,value2,value3, ...) [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | SET(value1,value2,value3, ...) [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | JSON
     *   | spatial_type
     *
     *   + aliases defined in BaseType class
     */
    public function parseColumnType(TokenList $tokenList): ColumnType
    {
        $type = $tokenList->expectMultiKeywordsEnum(BaseType::class);

        $settings = $tokenList->getSettings();
        if ($settings->canonicalizeTypes()) {
            $type = $type->canonicalize($settings);
        }

        [$size, $values, $unsigned, $zerofill, $charset, $collation, $srid] = $this->parseOptions($type, $tokenList);

        return new ColumnType($type, $size, $values, $unsigned, $charset, $collation, $srid, $zerofill);
    }

    public function parseCastType(TokenList $tokenList): CastType
    {
        $sign = null;
        if ($tokenList->hasKeyword(Keyword::SIGNED)) {
            $sign = true;
        } elseif ($tokenList->hasKeyword(Keyword::UNSIGNED)) {
            $sign = false;
        }

        if ($sign !== null) {
            $type = $tokenList->getMultiKeywordsEnum(BaseType::class);
        } else {
            $type = $tokenList->expectMultiKeywordsEnum(BaseType::class);
        }

        if ($type !== null) {
            $settings = $tokenList->getSettings();
            if ($settings->canonicalizeTypes()) {
                $type = $type->canonicalize($settings);
            }

            [$size, , , , $charset, $collation, $srid] = $this->parseOptions($type, $tokenList);
        } else {
            $size = $charset = $collation = $srid = null;
        }

        $array = $tokenList->hasKeyword(Keyword::ARRAY);

        return new CastType($type, $size, $sign, $array, $charset, $collation, $srid);
    }

    /**
     * @return array{non-empty-array<int>|null, non-empty-array<string>|null, bool, bool, Charset|null, Collation|null, int|null}
     */
    private function parseOptions(BaseType $type, TokenList $tokenList): array
    {
        $size = $values = $charset = $collation = $srid = null;
        $unsigned = $zerofill = false;

        if ($type->hasLength()) {
            if ($tokenList->hasSymbol('(')) {
                $length = (int) $tokenList->expectUnsignedInt();
                $decimals = null;
                if ($type->hasDecimals()) {
                    if ($type->equalsAny(BaseType::NUMERIC, BaseType::DECIMAL, BaseType::FLOAT)) {
                        if ($tokenList->hasSymbol(',')) {
                            $decimals = (int) $tokenList->expectUnsignedInt();
                        }
                    } else {
                        $tokenList->expectSymbol(',');
                        $decimals = (int) $tokenList->expectUnsignedInt();
                    }
                }
                $tokenList->expectSymbol(')');

                if ($decimals !== null) {
                    $size = [$length, $decimals];
                } else {
                    $size = [$length];
                }
            }
        } elseif ($type->hasValues()) {
            $tokenList->expectSymbol('(');
            $values = [];
            do {
                $values[] = $tokenList->expectString();
            } while ($tokenList->hasSymbol(','));
            $tokenList->expectSymbol(')');
        } elseif ($type->hasFsp() && $tokenList->hasSymbol('(')) {
            $size = [(int) $tokenList->expectUnsignedInt()];
            $tokenList->expectSymbol(')');
        }

        if ($type->isNumber()) {
            $unsigned = $tokenList->hasKeyword(Keyword::UNSIGNED);
            if ($unsigned === false) {
                $tokenList->passKeyword(Keyword::SIGNED);
            }
            $zerofill = $tokenList->hasKeyword(Keyword::ZEROFILL);
        }

        if ($type->hasCharset()) {
            if ($tokenList->hasKeywords(Keyword::CHARSET)) {
                $charset = $tokenList->expectCharsetName();
            } elseif ($tokenList->hasKeywords(Keyword::CHARACTER, Keyword::SET)) {
                $charset = $tokenList->expectCharsetName();
            } elseif ($tokenList->hasKeyword(Keyword::BINARY)) {
                $charset = Charset::get(Charset::BINARY);
            } elseif ($tokenList->hasKeyword(Keyword::ASCII)) {
                $charset = Charset::get(Charset::ASCII);
            }
            if ($tokenList->hasKeyword(Keyword::COLLATE)) {
                $collation = $tokenList->expectCollationName();
            }
        }

        if ($type->isSpatial()) {
            if ($tokenList->hasKeyword(Keyword::SRID)) {
                $srid = (int) $tokenList->expectUnsignedInt();
            }
        }

        return [$size, $values, $unsigned, $zerofill, $charset, $collation, $srid];
    }

    /**
     * [{FIELDS | COLUMNS}
     *   [TERMINATED BY 'string']
     *   [[OPTIONALLY] ENCLOSED BY 'char']
     *   [ESCAPED BY 'char']
     * ]
     * [LINES
     *   [STARTING BY 'string']
     *   [TERMINATED BY 'string']
     * ]
     */
    public function parseFileFormat(TokenList $tokenList): ?FileFormat
    {
        $fieldsTerminatedBy = $fieldsEnclosedBy = $fieldsEscapedBy = null;
        $optionallyEnclosed = false;
        if ($tokenList->hasAnyKeyword(Keyword::FIELDS, Keyword::COLUMNS)) {
            if ($tokenList->hasKeywords(Keyword::TERMINATED, Keyword::BY)) {
                $fieldsTerminatedBy = $tokenList->expectString();
            }
            $optionallyEnclosed = $tokenList->hasKeyword(Keyword::OPTIONALLY);
            if ($tokenList->hasKeywords(Keyword::ENCLOSED, Keyword::BY)) {
                $fieldsEnclosedBy = $tokenList->expectString();
            }
            if ($tokenList->hasKeywords(Keyword::ESCAPED, Keyword::BY)) {
                $fieldsEscapedBy = $tokenList->expectString();
            }
        }
        $linesStaringBy = $linesTerminatedBy = null;
        if ($tokenList->hasKeyword(Keyword::LINES)) {
            if ($tokenList->hasKeywords(Keyword::STARTING, Keyword::BY)) {
                $linesStaringBy = $tokenList->expectString();
            }
            if ($tokenList->hasKeywords(Keyword::TERMINATED, Keyword::BY)) {
                $linesTerminatedBy = $tokenList->expectString();
            }
        }

        if ($fieldsTerminatedBy !== null || $fieldsEnclosedBy !== null || $fieldsEscapedBy !== null || $linesStaringBy !== null || $linesTerminatedBy !== null) {
            return new FileFormat(
                $fieldsTerminatedBy,
                $fieldsEnclosedBy,
                $fieldsEscapedBy,
                $optionallyEnclosed,
                $linesStaringBy,
                $linesTerminatedBy
            );
        }

        return null;
    }

}
