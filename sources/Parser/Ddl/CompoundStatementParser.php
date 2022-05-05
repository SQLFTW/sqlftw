<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable PSR2.Methods.FunctionCallSignature.MultipleArguments

namespace SqlFtw\Parser\Ddl;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\Dml\QueryParser;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Ddl\Compound\CaseStatement;
use SqlFtw\Sql\Ddl\Compound\CloseCursorStatement;
use SqlFtw\Sql\Ddl\Compound\CompoundStatement;
use SqlFtw\Sql\Ddl\Compound\Condition;
use SqlFtw\Sql\Ddl\Compound\ConditionInformationItem;
use SqlFtw\Sql\Ddl\Compound\ConditionType;
use SqlFtw\Sql\Ddl\Compound\DeclareConditionStatement;
use SqlFtw\Sql\Ddl\Compound\DeclareCursorStatement;
use SqlFtw\Sql\Ddl\Compound\DeclareHandlerStatement;
use SqlFtw\Sql\Ddl\Compound\DeclareStatement;
use SqlFtw\Sql\Ddl\Compound\DiagnosticsArea;
use SqlFtw\Sql\Ddl\Compound\DiagnosticsItem;
use SqlFtw\Sql\Ddl\Compound\FetchStatement;
use SqlFtw\Sql\Ddl\Compound\GetDiagnosticsStatement;
use SqlFtw\Sql\Ddl\Compound\HandlerAction;
use SqlFtw\Sql\Ddl\Compound\IfStatement;
use SqlFtw\Sql\Ddl\Compound\IterateStatement;
use SqlFtw\Sql\Ddl\Compound\LeaveStatement;
use SqlFtw\Sql\Ddl\Compound\LoopStatement;
use SqlFtw\Sql\Ddl\Compound\OpenCursorStatement;
use SqlFtw\Sql\Ddl\Compound\RepeatStatement;
use SqlFtw\Sql\Ddl\Compound\ResignalStatement;
use SqlFtw\Sql\Ddl\Compound\ReturnStatement;
use SqlFtw\Sql\Ddl\Compound\SignalStatement;
use SqlFtw\Sql\Ddl\Compound\StatementInformationItem;
use SqlFtw\Sql\Ddl\Compound\WhileStatement;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Statement;

class CompoundStatementParser
{
    use StrictBehaviorMixin;

    /** @var Parser */
    private $parser;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var TypeParser */
    private $typeParser;

    /** @var QueryParser */
    private $queryParser;

    public function __construct(
        Parser $parser,
        ExpressionParser $expressionParser,
        TypeParser $typeParser,
        QueryParser $queryParser
    ) {
        $this->parser = $parser;
        $this->expressionParser = $expressionParser;
        $this->typeParser = $typeParser;
        $this->queryParser = $queryParser;
    }

    /**
     * [begin_label:] BEGIN
     *     [statement_list]
     * END [end_label]
     */
    public function parseCompoundStatement(TokenList $tokenList): CompoundStatement
    {
        $label = null;
        if (!$tokenList->hasKeyword(Keyword::BEGIN)) {
            $label = $tokenList->getName();
            if ($label !== null) {
                $tokenList->expect(TokenType::DOUBLE_COLON);
            }
            $tokenList->expectKeyword(Keyword::BEGIN);
        }

        return $this->parseBlock($tokenList, $label);
    }

    /**
     * @return Statement[]
     */
    private function parseStatementList(TokenList $tokenList): array
    {
        $statements = [];
        do {
            if ($tokenList->hasAnyKeyword(Keyword::END, Keyword::UNTIL, Keyword::WHEN, Keyword::ELSE, Keyword::ELSEIF)) {
                $tokenList->resetPosition(-1);
                break;
            }
            $statements[] = $this->parseStatement($tokenList);
        } while ($tokenList->has(TokenType::SEMICOLON));

        return $statements;
    }

    /**
     * RETURN expr
     *
     * LEAVE label
     *
     * ITERATE label
     *
     * OPEN cursor_name
     *
     * CLOSE cursor_name
     *
     * ...
     */
    private function parseStatement(TokenList $tokenList): Statement
    {
        $label = $tokenList->getNonKeywordName();
        if ($label !== null) {
            $tokenList->expect(TokenType::DOUBLE_COLON);
            $keyword = $tokenList->expectAnyKeyword(Keyword::BEGIN, Keyword::LOOP, Keyword::REPEAT, Keyword::WHILE);
        } else {
            $keyword = $tokenList->getAnyKeyword(
                Keyword::BEGIN, Keyword::LOOP, Keyword::REPEAT, Keyword::WHILE, Keyword::CASE, Keyword::IF,
                Keyword::DECLARE, Keyword::OPEN, Keyword::FETCH, Keyword::CLOSE, Keyword::GET, Keyword::SIGNAL,
                Keyword::RESIGNAL, Keyword::RETURN, Keyword::LEAVE, Keyword::ITERATE
            );
        }
        switch ($keyword) {
            case Keyword::BEGIN:
                return $this->parseBlock($tokenList, $label);
            case Keyword::LOOP:
                return $this->parseLoop($tokenList, $label);
            case Keyword::REPEAT:
                return $this->parseRepeat($tokenList, $label);
            case Keyword::WHILE:
                return $this->parseWhile($tokenList, $label);
            case Keyword::CASE:
                return $this->parseCase($tokenList);
            case Keyword::IF:
                return $this->parseIf($tokenList);
            case Keyword::DECLARE:
                return $this->parseDeclare($tokenList);
            case Keyword::OPEN:
                return new OpenCursorStatement($tokenList->expectName());
            case Keyword::FETCH:
                return $this->parseFetch($tokenList);
            case Keyword::CLOSE:
                return new CloseCursorStatement($tokenList->expectName());
            case Keyword::GET:
                return $this->parseGetDiagnostics($tokenList);
            case Keyword::SIGNAL:
            case Keyword::RESIGNAL:
                return $this->parseSignalResignal($tokenList, $keyword); // @phpstan-ignore-line non-null
            case Keyword::RETURN:
                return new ReturnStatement($this->expressionParser->parseExpression($tokenList));
            case Keyword::LEAVE:
                return new LeaveStatement($tokenList->expectName());
            case Keyword::ITERATE:
                return new IterateStatement($tokenList->expectName());
            default:
                return $this->parser->parseTokenList($tokenList);
        }
    }

    private function parseBlock(TokenList $tokenList, ?string $label): CompoundStatement
    {
        $statements = $this->parseStatementList($tokenList);
        $tokenList->expectKeyword(Keyword::END);

        if ($label !== null) {
            $endLabel = $tokenList->getName();
            if ($endLabel !== null && $endLabel !== $label) {
                $tokenList->expected($label);
            }
        }

        return new CompoundStatement($statements, $label);
    }

    /**
     * [begin_label:] LOOP
     *     statement_list
     * END LOOP [end_label]
     */
    private function parseLoop(TokenList $tokenList, ?string $label): LoopStatement
    {
        $statements = $this->parseStatementList($tokenList);
        $tokenList->expectKeywords(Keyword::END, Keyword::LOOP);

        if ($label !== null) {
            $endLabel = $tokenList->getName();
            if ($endLabel !== null && $endLabel !== $label) {
                $tokenList->expected($label);
            }
        }

        return new LoopStatement($statements, $label);
    }

    /**
     * [begin_label:] REPEAT
     *     statement_list
     *     UNTIL search_condition
     * END REPEAT [end_label]
     */
    private function parseRepeat(TokenList $tokenList, ?string $label): RepeatStatement
    {
        $statements = $this->parseStatementList($tokenList);
        $tokenList->expectKeyword(Keyword::UNTIL);
        $condition = $this->expressionParser->parseExpression($tokenList);
        $tokenList->expectKeywords(Keyword::END, Keyword::REPEAT);

        if ($label !== null) {
            $endLabel = $tokenList->getName();
            if ($endLabel !== null && $endLabel !== $label) {
                $tokenList->expected($label);
            }
        }

        return new RepeatStatement($statements, $condition, $label);
    }

    /**
     * [begin_label:] WHILE search_condition DO
     *     statement_list
     * END WHILE [end_label]
     */
    private function parseWhile(TokenList $tokenList, ?string $label): WhileStatement
    {
        $condition = $this->expressionParser->parseExpression($tokenList);
        $tokenList->expectKeyword(Keyword::DO);
        $statements = $this->parseStatementList($tokenList);
        $tokenList->expectKeywords(Keyword::END, Keyword::REPEAT);

        if ($label !== null) {
            $endLabel = $tokenList->getName();
            if ($endLabel !== null && $endLabel !== $label) {
                $tokenList->expected($label);
            }
        }

        return new WhileStatement($statements, $condition, $label);
    }

    /**
     * CASE case_value
     *     WHEN when_value THEN statement_list
     *     [WHEN when_value THEN statement_list] ...
     *     [ELSE statement_list]
     * END CASE
     *
     * CASE
     *     WHEN search_condition THEN statement_list
     *     [WHEN search_condition THEN statement_list] ...
     *     [ELSE statement_list]
     * END CASE
     */
    private function parseCase(TokenList $tokenList): CaseStatement
    {
        $condition = null;
        if (!$tokenList->hasKeyword(Keyword::WHEN)) {
            $condition = $this->expressionParser->parseExpression($tokenList);
            $tokenList->expectKeyword(Keyword::WHEN);
        }
        $values = $statementLists = [];
        do {
            $values[] = $this->expressionParser->parseExpression($tokenList);
            $tokenList->expectKeyword(Keyword::THEN);
            $statementLists[] = $this->parseStatementList($tokenList);
        } while ($tokenList->hasKeyword(Keyword::WHEN));

        if ($tokenList->hasKeyword(Keyword::ELSE)) {
            $statementLists[] = $this->parseStatementList($tokenList);
        }
        $tokenList->expectKeywords(Keyword::END, Keyword::CASE);

        return new CaseStatement($condition, $values, $statementLists);
    }

    /**
     * IF search_condition THEN statement_list
     *     [ELSEIF search_condition THEN statement_list] ...
     *     [ELSE statement_list]
     * END IF
     */
    private function parseIf(TokenList $tokenList): IfStatement
    {
        $conditions = $statementLists = [];
        $conditions[] = $this->expressionParser->parseExpression($tokenList);
        $tokenList->expectKeyword(Keyword::THEN);
        $statementLists[] = $this->parseStatementList($tokenList);

        while ($tokenList->hasKeyword(Keyword::ELSEIF)) {
            $conditions[] = $this->expressionParser->parseExpression($tokenList);
            $tokenList->expectKeyword(Keyword::THEN);
            $statementLists[] = $this->parseStatementList($tokenList);
        }
        if ($tokenList->hasKeyword(Keyword::ELSE)) {
            $statementLists[] = $this->parseStatementList($tokenList);
        }
        $tokenList->expectKeywords(Keyword::END, Keyword::IF);

        return new IfStatement($conditions, $statementLists);
    }

    /**
     * DECLARE var_name [, var_name] ... type [DEFAULT value]
     *
     *
     * DECLARE cursor_name CURSOR FOR select_statement
     *
     *
     * DECLARE condition_name CONDITION FOR condition_value
     *
     * condition_value:
     *     mysql_error_code
     *   | SQLSTATE [VALUE] sqlstate_value
     *
     *
     * DECLARE handler_action HANDLER
     *     FOR condition_value [, condition_value] ...
     *     statement
     *
     * handler_action:
     *     CONTINUE
     *   | EXIT
     *   | UNDO
     *
     * condition_value:
     *     mysql_error_code
     *   | SQLSTATE [VALUE] sqlstate_value
     *   | condition_name
     *   | SQLWARNING
     *   | NOT FOUND
     *   | SQLEXCEPTION
     *
     * @return DeclareStatement|DeclareCursorStatement|DeclareConditionStatement|DeclareHandlerStatement
     */
    private function parseDeclare(TokenList $tokenList)
    {
        /** @var HandlerAction|null $action */
        $action = $tokenList->getKeywordEnum(HandlerAction::class);
        if ($action !== null) {
            $tokenList->expectKeywords(Keyword::HANDLER, Keyword::FOR);

            $conditions = [];
            do {
                $value = null;
                if ($tokenList->hasKeywords(Keyword::NOT, Keyword::FOUND)) {
                    $type = ConditionType::get(ConditionType::NOT_FOUND);
                } elseif ($tokenList->hasKeyword(Keyword::SQLEXCEPTION)) {
                    $type = ConditionType::get(ConditionType::SQL_EXCEPTION);
                } elseif ($tokenList->hasKeyword(Keyword::SQLWARNING)) {
                    $type = ConditionType::get(ConditionType::SQL_WARNING);
                } elseif ($tokenList->hasKeyword(Keyword::SQLSTATE)) {
                    $type = ConditionType::get(ConditionType::SQL_STATE);
                    $value = $tokenList->getInt();
                    if ($value === null) {
                        $value = $tokenList->expectNameOrString();
                    }
                } else {
                    $value = $tokenList->getName();
                    if ($value !== null) {
                        $type = ConditionType::get(ConditionType::CONDITION);
                    } else {
                        $value = $tokenList->expectInt();
                        $type = ConditionType::get(ConditionType::ERROR);
                    }
                }
                $conditions[] = new Condition($type, $value);
            } while ($tokenList->hasComma());

            $statement = $this->parseStatement($tokenList);

            return new DeclareHandlerStatement($action, $conditions, $statement);
        }

        $name = $tokenList->expectName();

        if ($tokenList->hasKeyword(Keyword::CURSOR)) {
            $tokenList->expectKeyword(Keyword::FOR);
            $query = $this->queryParser->parseQuery($tokenList);

            return new DeclareCursorStatement($name, $query);
        } elseif ($tokenList->hasKeyword(Keyword::CONDITION)) {
            $tokenList->expectKeywords(Keyword::FOR);
            if ($tokenList->hasKeyword(Keyword::SQLSTATE)) {
                $tokenList->passKeyword(Keyword::VALUE);
                $value = $tokenList->getInt();
                if ($value === null) {
                    $value = $tokenList->expectNameOrString();
                }
            } else {
                $value = $tokenList->expectInt();
            }

            return new DeclareConditionStatement($name, $value);
        }

        $names = [$name];
        while ($tokenList->hasComma()) {
            $names[] = $tokenList->expectName();
        }
        $type = $this->typeParser->parseType($tokenList);
        $default = null;
        if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
            $default = $this->expressionParser->parseLiteralValue($tokenList);
        }

        return new DeclareStatement($names, $type, $default);
    }

    /**
     * FETCH [[NEXT] FROM] cursor_name INTO var_name [, var_name] ...
     */
    private function parseFetch(TokenList $tokenList): FetchStatement
    {
        if ($tokenList->hasKeyword(Keyword::NEXT)) {
            $tokenList->expectKeyword(Keyword::FROM);
        } else {
            $tokenList->passKeyword(Keyword::FROM);
        }
        $cursor = $tokenList->expectName();
        $tokenList->expectKeyword(Keyword::INTO);
        $variables = [];
        do {
            $variables[] = $tokenList->expectName();
        } while ($tokenList->hasComma());

        return new FetchStatement($cursor, $variables);
    }

    /**
     * GET [CURRENT | STACKED] DIAGNOSTICS
     * {
     *     statement_information_item
     *  [, statement_information_item] ...
     *   | CONDITION condition_number
     *     condition_information_item
     *  [, condition_information_item] ...
     * }
     *
     * statement_information_item:
     *     target = statement_information_item_name
     *
     * condition_information_item:
     *     target = condition_information_item_name
     *
     * statement_information_item_name:
     *     NUMBER
     *   | ROW_COUNT
     *
     * condition_information_item_name:
     *     CLASS_ORIGIN
     *   | SUBCLASS_ORIGIN
     *   | RETURNED_SQLSTATE
     *   | MESSAGE_TEXT
     *   | MYSQL_ERRNO
     *   | CONSTRAINT_CATALOG
     *   | CONSTRAINT_SCHEMA
     *   | CONSTRAINT_NAME
     *   | CATALOG_NAME
     *   | SCHEMA_NAME
     *   | TABLE_NAME
     *   | COLUMN_NAME
     *   | CURSOR_NAME
     *
     * condition_number, target:
     *     (see following discussion)
     */
    private function parseGetDiagnostics(TokenList $tokenList): GetDiagnosticsStatement
    {
        /** @var DiagnosticsArea|null $area */
        $area = $tokenList->getKeywordEnum(DiagnosticsArea::class);
        $tokenList->expectKeyword(Keyword::DIAGNOSTICS);

        $statementItems = $conditionItems = $conditionNumber = null;
        if ($tokenList->hasKeyword(Keyword::CONDITION)) {
            $conditionNumber = $tokenList->expectInt();
            $conditionItems = [];
            do {
                $target = $tokenList->expectName();
                $tokenList->expectOperator(Operator::EQUAL);
                /** @var ConditionInformationItem $item */
                $item = $tokenList->expectKeywordEnum(ConditionInformationItem::class);
                $conditionItems[] = new DiagnosticsItem($target, $item);
            } while ($tokenList->hasComma());
        } else {
            $statementItems = [];
            do {
                $target = $tokenList->expectName();
                $tokenList->expectOperator(Operator::EQUAL);
                /** @var StatementInformationItem $item */
                $item = $tokenList->expectKeywordEnum(StatementInformationItem::class);
                $statementItems[] = new DiagnosticsItem($target, $item);
            } while ($tokenList->hasComma());
        }

        return new GetDiagnosticsStatement($area, $statementItems, $conditionNumber, $conditionItems);
    }

    /**
     * SIGNAL [condition_value]
     *     [SET signal_information_item
     *     [, signal_information_item] ...]
     *
     * RESIGNAL [condition_value]
     *     [SET signal_information_item
     *     [, signal_information_item] ...]
     *
     * condition_value:
     *     SQLSTATE [VALUE] sqlstate_value
     *   | condition_name
     *
     * signal_information_item:
     *     condition_information_item_name = simple_value_specification
     *
     * condition_information_item_name:
     *     CLASS_ORIGIN
     *   | SUBCLASS_ORIGIN
     *   | MESSAGE_TEXT
     *   | MYSQL_ERRNO
     *   | CONSTRAINT_CATALOG
     *   | CONSTRAINT_SCHEMA
     *   | CONSTRAINT_NAME
     *   | CATALOG_NAME
     *   | SCHEMA_NAME
     *   | TABLE_NAME
     *   | COLUMN_NAME
     *   | CURSOR_NAME
     *
     * condition_name, simple_value_specification:
     *     (see following discussion)
     *
     * Valid simple_value_specification designators can be specified using:
     *  - stored procedure or function parameters,
     *  - stored program local variables declared with DECLARE,
     *  - user-defined variables,
     *  - system variables, or
     *  - literals. A character literal may include a _charset introducer.
     *
     * @return SignalStatement|ResignalStatement
     */
    private function parseSignalResignal(TokenList $tokenList, string $keyword)
    {
        $condition = $tokenList->getInt();
        if ($condition === null) {
            $tokenList->expectKeyword(Keyword::SQLSTATE);
            $tokenList->passKeyword(Keyword::VALUE);
            $condition = $tokenList->expectNameOrString();
        }
        $items = [];
        if ($tokenList->hasKeyword(Keyword::SET)) {
            do {
                $item = $tokenList->expectKeywordEnum(ConditionInformationItem::class)->getValue();
                $tokenList->expectOperator(Operator::EQUAL);
                $value = $this->expressionParser->parseLiteralValue($tokenList);
                $items[$item] = $value;
            } while ($tokenList->hasComma());
        }

        if ($keyword === Keyword::SIGNAL) {
            return new SignalStatement($condition, $items);
        } else {
            return new ResignalStatement($condition, $items);
        }
    }

}
