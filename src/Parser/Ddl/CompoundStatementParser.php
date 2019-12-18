<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\Dml\SelectCommandParser;
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

    /** @var \SqlFtw\Parser\Parser */
    private $parser;

    /** @var \SqlFtw\Parser\ExpressionParser */
    private $expressionParser;

    /** @var \SqlFtw\Parser\Ddl\TypeParser */
    private $typeParser;

    /** @var \SqlFtw\Parser\Dml\SelectCommandParser */
    private $selectCommandParser;

    public function __construct(
        Parser $parser,
        ExpressionParser $expressionParser,
        TypeParser $typeParser,
        SelectCommandParser $selectCommandParser
    ) {
        $this->parser = $parser;
        $this->expressionParser = $expressionParser;
        $this->typeParser = $typeParser;
        $this->selectCommandParser = $selectCommandParser;
    }

    /**
     * [begin_label:] BEGIN
     *     [statement_list]
     * END [end_label]
     */
    public function parseCompoundStatement(TokenList $tokenList): CompoundStatement
    {
        $label = null;
        if (!$tokenList->mayConsumeKeyword(Keyword::BEGIN)) {
            $label = $tokenList->mayConsumeName();
            if ($label !== null) {
                $tokenList->consume(TokenType::DOUBLE_COLON);
            }
            $tokenList->consumeKeyword(Keyword::BEGIN);
        }

        return $this->parseBlock($tokenList, $label);
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Statement[]
     */
    private function parseStatementList(TokenList $tokenList): array
    {
        $statements = [];
        do {
            if ($tokenList->mayConsumeAnyKeyword(Keyword::END, Keyword::UNTIL, Keyword::WHEN, Keyword::ELSE, Keyword::ELSEIF)) {
                $tokenList->resetPosition(-1);
                break;
            }
            $statements[] = $this->parseStatement($tokenList);
        } while ($tokenList->mayConsume(TokenType::SEMICOLON));

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
        $label = $tokenList->mayConsumeName();
        if ($label !== null) {
            $tokenList->consume(TokenType::DOUBLE_COLON);
            $keyword = $tokenList->consumeAnyKeyword(Keyword::BEGIN, Keyword::LOOP, Keyword::REPEAT, Keyword::WHILE);
        } else {
            $keyword = $tokenList->mayConsumeAnyKeyword(
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
                return new OpenCursorStatement($tokenList->consumeName());
            case Keyword::FETCH:
                return $this->parseFetch($tokenList);
            case Keyword::CLOSE:
                return new CloseCursorStatement($tokenList->consumeName());
            case Keyword::GET:
                return $this->parseGetDiagnostics($tokenList);
            case Keyword::SIGNAL:
            case Keyword::RESIGNAL:
                return $this->parseSignalResignal($tokenList, $keyword);
            case Keyword::RETURN:
                return new ReturnStatement($this->expressionParser->parseExpression($tokenList));
            case Keyword::LEAVE:
                return new LeaveStatement($tokenList->consumeName());
            case Keyword::ITERATE:
                return new IterateStatement($tokenList->consumeName());
            default:
                return $this->parser->parseTokenList($tokenList);
        }
    }

    private function parseBlock(TokenList $tokenList, ?string $label): CompoundStatement
    {
        $statements = $this->parseStatementList($tokenList);
        $tokenList->consumeKeyword(Keyword::END);

        $endLabel = $tokenList->mayConsumeName();
        if ($endLabel !== null && $endLabel !== $label) {
            $tokenList->expected($label);
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
        $tokenList->consumeKeywords(Keyword::END, Keyword::LOOP);

        $endLabel = $tokenList->mayConsumeName();
        if ($endLabel !== null && $endLabel !== $label) {
            $tokenList->expected($label);
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
        $tokenList->consumeKeyword(Keyword::UNTIL);
        $condition = $this->expressionParser->parseExpression($tokenList);
        $tokenList->consumeKeywords(Keyword::END, Keyword::REPEAT);

        $endLabel = $tokenList->mayConsumeName();
        if ($endLabel !== null && $endLabel !== $label) {
            $tokenList->expected($label);
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
        $tokenList->consumeKeyword(Keyword::DO);
        $statements = $this->parseStatementList($tokenList);
        $tokenList->consumeKeywords(Keyword::END, Keyword::REPEAT);

        $endLabel = $tokenList->mayConsumeName();
        if ($endLabel !== null && $endLabel !== $label) {
            $tokenList->expected($label);
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
        if (!$tokenList->mayConsumeKeyword(Keyword::WHEN)) {
            $condition = $this->expressionParser->parseExpression($tokenList);
            $tokenList->consumeKeyword(Keyword::WHEN);
        }
        $values = $statementLists = [];
        do {
            $values[] = $this->expressionParser->parseExpression($tokenList);
            $tokenList->consumeKeyword(Keyword::THEN);
            $statementLists[] = $this->parseStatementList($tokenList);
        } while ($tokenList->mayConsumeKeyword(Keyword::WHEN));

        if ($tokenList->mayConsumeKeyword(Keyword::ELSE)) {
            $statementLists[] = $this->parseStatementList($tokenList);
        }
        $tokenList->consumeKeywords(Keyword::END, Keyword::CASE);

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
        $tokenList->consumeKeyword(Keyword::THEN);
        $statementLists[] = $this->parseStatementList($tokenList);

        while ($tokenList->mayConsumeKeyword(Keyword::ELSEIF)) {
            $conditions[] = $this->expressionParser->parseExpression($tokenList);
            $tokenList->consumeKeyword(Keyword::THEN);
            $statementLists[] = $this->parseStatementList($tokenList);
        }
        if ($tokenList->mayConsumeKeyword(Keyword::ELSE)) {
            $statementLists[] = $this->parseStatementList($tokenList);
        }
        $tokenList->consumeKeywords(Keyword::END, Keyword::IF);

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
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Ddl\Compound\DeclareStatement|\SqlFtw\Sql\Ddl\Compound\DeclareCursorStatement|\SqlFtw\Sql\Ddl\Compound\DeclareConditionStatement|\SqlFtw\Sql\Ddl\Compound\DeclareHandlerStatement
     */
    private function parseDeclare(TokenList $tokenList)
    {
        $tokenList->consumeKeyword(Keyword::DECLARE);

        /** @var \SqlFtw\Sql\Ddl\Compound\HandlerAction|null $action */
        $action = $tokenList->mayConsumeKeywordEnum(HandlerAction::class);
        if ($action !== null) {
            $tokenList->consumeKeywords(Keyword::HANDLER, Keyword::FOR);

            $conditions = [];
            do {
                $value = null;
                if ($tokenList->mayConsumeKeywords(Keyword::NOT, Keyword::FOUND)) {
                    $type = ConditionType::get(ConditionType::NOT_FOUND);
                } elseif ($tokenList->mayConsumeKeyword(Keyword::SQLEXCEPTION)) {
                    $type = ConditionType::get(ConditionType::SQL_EXCEPTION);
                } elseif ($tokenList->mayConsumeKeyword(Keyword::SQLWARNING)) {
                    $type = ConditionType::get(ConditionType::SQL_WARNING);
                } elseif ($tokenList->mayConsumeKeyword(Keyword::SQLSTATE)) {
                    $type = ConditionType::get(ConditionType::SQL_STATE);
                    $value = $tokenList->mayConsumeInt();
                    if ($value === null) {
                        $value = $tokenList->consumeNameOrString();
                    }
                } else {
                    $value = $tokenList->mayConsumeName();
                    if ($value !== null) {
                        $type = ConditionType::get(ConditionType::CONDITION);
                    } else {
                        $value = $tokenList->consumeInt();
                        $type = ConditionType::get(ConditionType::ERROR);
                    }
                }
                $conditions[] = new Condition($type, $value);
            } while ($tokenList->mayConsumeComma());

            $statement = $this->parseStatement($tokenList);

            return new DeclareHandlerStatement($action, $conditions, $statement);
        }

        $name = $tokenList->consumeName();

        if ($tokenList->mayConsumeKeyword(Keyword::CURSOR)) {
            $tokenList->consumeKeyword(Keyword::FOR);
            $select = $this->selectCommandParser->parseSelect($tokenList);

            return new DeclareCursorStatement($name, $select);
        } elseif ($tokenList->mayConsumeKeyword(Keyword::CONDITION)) {
            $tokenList->consumeKeywords(Keyword::FOR);
            if ($tokenList->mayConsumeKeyword(Keyword::SQLSTATE)) {
                $tokenList->mayConsumeKeyword(Keyword::VALUE);
                $value = $tokenList->mayConsumeInt();
                if ($value === null) {
                    $value = $tokenList->consumeNameOrString();
                }
            } else {
                $value = $tokenList->consumeInt();
            }

            return new DeclareConditionStatement($name, $value);
        }

        $names = [$name];
        while ($tokenList->mayConsumeComma()) {
            $names[] = $tokenList->consumeName();
        }
        $type = $this->typeParser->parseType($tokenList);
        $default = null;
        if ($tokenList->mayConsumeKeyword(Keyword::DEFAULT)) {
            $default = $this->expressionParser->parseLiteralValue($tokenList);
        }

        return new DeclareStatement($names, $type, $default);
    }

    /**
     * FETCH [[NEXT] FROM] cursor_name INTO var_name [, var_name] ...
     */
    private function parseFetch(TokenList $tokenList): FetchStatement
    {
        if ($tokenList->mayConsumeKeyword(Keyword::NEXT)) {
            $tokenList->consumeKeyword(Keyword::FROM);
        } else {
            $tokenList->mayConsumeKeyword(Keyword::FROM);
        }
        $cursor = $tokenList->consumeName();
        $tokenList->consumeKeyword(Keyword::INTO);
        $variables = [];
        do {
            $variables[] = $tokenList->consumeName();
        } while ($tokenList->mayConsumeComma());

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
        /** @var \SqlFtw\Sql\Ddl\Compound\DiagnosticsArea|null $area */
        $area = $tokenList->mayConsumeKeywordEnum(DiagnosticsArea::class);
        $tokenList->consumeKeyword(Keyword::DIAGNOSTICS);

        $statementItems = $conditionItems = null;
        if ($tokenList->mayConsumeKeyword(Keyword::CONDITION)) {
            $conditionItems = [];
            do {
                $target = $tokenList->consumeName();
                $tokenList->consumeOperator(Operator::EQUAL);
                /** @var \SqlFtw\Sql\Ddl\Compound\ConditionInformationItem $item */
                $item = $tokenList->consumeKeywordEnum(ConditionInformationItem::class);
                $conditionItems[] = new DiagnosticsItem($target, $item);
            } while ($tokenList->mayConsumeComma());
        } else {
            $statementItems = [];
            do {
                $target = $tokenList->consumeName();
                $tokenList->consumeOperator(Operator::EQUAL);
                /** @var \SqlFtw\Sql\Ddl\Compound\StatementInformationItem $item */
                $item = $tokenList->consumeKeywordEnum(StatementInformationItem::class);
                $statementItems[] = new DiagnosticsItem($target, $item);
            } while ($tokenList->mayConsumeComma());
        }

        return new GetDiagnosticsStatement($conditionItems, $statementItems, $area);
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
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @param string $keyword
     * @return \SqlFtw\Sql\Ddl\Compound\SignalStatement|\SqlFtw\Sql\Ddl\Compound\ResignalStatement
     */
    private function parseSignalResignal(TokenList $tokenList, string $keyword)
    {
        $condition = $tokenList->mayConsumeInt();
        if ($condition === null && $tokenList->consumeKeyword(Keyword::SQLSTATE)) {
            $tokenList->mayConsumeKeyword(Keyword::VALUE);
            $condition = $tokenList->consumeNameOrString();
        }
        $items = null;
        if ($tokenList->mayConsumeKeyword(Keyword::SET)) {
            $items = [];
            do {
                $item = $tokenList->consumeKeywordEnum(ConditionInformationItem::class)->getValue();
                $tokenList->consumeOperator(Operator::EQUAL);
                $value = $this->expressionParser->parseLiteralValue($tokenList);
                $items[$item] = $value;
            } while ($tokenList->mayConsumeComma());
        }

        if ($keyword === Keyword::SIGNAL) {
            return new SignalStatement($condition, $items);
        } else {
            return new ResignalStatement($condition, $items);
        }
    }

}
