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
use SqlFtw\Parser\InvalidCommand;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserException;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Command;
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
use SqlFtw\Sql\Ddl\Event\AlterEventCommand;
use SqlFtw\Sql\Ddl\Event\CreateEventCommand;
use SqlFtw\Sql\Ddl\Event\EventCommand;
use SqlFtw\Sql\Ddl\View\AlterViewCommand;
use SqlFtw\Sql\Dml\Load\LoadDataCommand;
use SqlFtw\Sql\Dml\Load\LoadXmlCommand;
use SqlFtw\Sql\Dml\Prepared\PreparedStatementCommand;
use SqlFtw\Sql\Dml\Transaction\LockTablesCommand;
use SqlFtw\Sql\Dml\Transaction\UnlockTablesCommand;
use SqlFtw\Sql\Dml\Utility\ExplainForConnectionCommand;
use SqlFtw\Sql\Entity;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\IntLiteral;
use SqlFtw\Sql\Expression\NullLiteral;
use SqlFtw\Sql\Expression\NumberLiteral;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Expression\StringLiteral;
use SqlFtw\Sql\Expression\UintLiteral;
use SqlFtw\Sql\Expression\UserVariable;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Routine;
use SqlFtw\Sql\Statement;

class CompoundStatementParser
{
    use StrictBehaviorMixin;

    /** @var Parser */
    private $parser;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var QueryParser */
    private $queryParser;

    public function __construct(Parser $parser, ExpressionParser $expressionParser, QueryParser $queryParser)
    {
        $this->parser = $parser;
        $this->expressionParser = $expressionParser;
        $this->queryParser = $queryParser;
    }

    /**
     * routine_body:
     *     RETURN ...
     *   | statement
     *   | compound_statement
     *
     * compound_statement:
     *   [begin_label:] BEGIN
     *     [statement_list]
     *   END [end_label]
     */
    public function parseRoutineBody(TokenList $tokenList, string $routine): Statement
    {
        if ($routine === Routine::FUNCTION && $tokenList->hasKeyword(Keyword::RETURN)) {
            return new ReturnStatement($this->expressionParser->parseExpression($tokenList));
        }

        $position = $tokenList->getPosition();
        $label = $tokenList->getNonKeywordName(Entity::LABEL);
        if ($label !== null) {
            $tokenList->expectSymbol(':');
        }

        $previous = $tokenList->inEmbedded();
        $tokenList->startEmbedded();
        $tokenList->startRoutine($routine);

        if ($tokenList->hasAnyKeyword(Keyword::BEGIN, Keyword::LOOP, Keyword::REPEAT, Keyword::WHILE, Keyword::CASE, Keyword::IF)) {
            $statement = $this->parseStatement($tokenList->rewind($position));
        } else {
            $statement = $this->parseCommand($tokenList->rewind($position), true);
        }

        $previous ? $tokenList->startEmbedded() : $tokenList->endEmbedded();
        $tokenList->endRoutine();

        return $statement;
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
        $position = $tokenList->getPosition();
        $label = $tokenList->getNonReservedName(Entity::LABEL);
        if (!$tokenList->hasSymbol(':')) {
            $label = null;
            $tokenList->rewind($position);
        }

        $position = $tokenList->getPosition();
        $in = $tokenList->inRoutine();
rd($in);
        if ($label !== null) {
            $keyword = $tokenList->expectAnyKeyword(Keyword::BEGIN, Keyword::LOOP, Keyword::REPEAT, Keyword::WHILE);
        } else {
            $keywords = [
                Keyword::BEGIN, Keyword::LOOP, Keyword::REPEAT, Keyword::WHILE, Keyword::CASE, Keyword::IF, Keyword::DECLARE,
                Keyword::OPEN, Keyword::FETCH, Keyword::CLOSE, Keyword::GET, Keyword::SIGNAL, Keyword::RESIGNAL, Keyword::LEAVE, Keyword::ITERATE
            ];
            if ($in === Routine::FUNCTION) {
                $keywords[] = Keyword::RETURN;
            }
            $keyword = $tokenList->getAnyKeyword(...$keywords);
        }
        switch ($keyword) {
            case Keyword::LOOP:
                $statement = $this->parseLoop($tokenList, $label);
                break;
            case Keyword::REPEAT:
                $statement = $this->parseRepeat($tokenList, $label);
                break;
            case Keyword::WHILE:
                $statement = $this->parseWhile($tokenList, $label);
                break;
            case Keyword::CASE:
                $statement = $this->parseCase($tokenList);
                break;
            case Keyword::IF:
                $statement = $this->parseIf($tokenList);
                break;
            case Keyword::DECLARE:
                $statement = $this->parseDeclare($tokenList);
                break;
            case Keyword::OPEN:
                $statement = new OpenCursorStatement($tokenList->expectName(null));
                break;
            case Keyword::FETCH:
                $statement = $this->parseFetch($tokenList);
                break;
            case Keyword::CLOSE:
                $statement = new CloseCursorStatement($tokenList->expectName(null));
                break;
            case Keyword::GET:
                $statement = $this->parseGetDiagnostics($tokenList->rewind($position));
                break;
            case Keyword::SIGNAL:
            case Keyword::RESIGNAL:
                $statement = $this->parseSignalResignal($tokenList->rewind(-1));
                break;
            case Keyword::RETURN:
                $statement = new ReturnStatement($this->expressionParser->parseExpression($tokenList));
                break;
            case Keyword::LEAVE:
                $statement = new LeaveStatement($tokenList->expectName(Entity::LABEL));
                break;
            case Keyword::ITERATE:
                $statement = new IterateStatement($tokenList->expectName(Entity::LABEL));
                break;
            case Keyword::BEGIN:
                $statement = $this->parseBlock($tokenList, $label);
                break;
            default:
                $previous = $tokenList->inEmbedded();
                // do not check delimiter in Parser, because it will be checked here
                $tokenList->startEmbedded();
                $statement = $this->parseCommand($tokenList, $previous);
                $previous ? $tokenList->startEmbedded() : $tokenList->endEmbedded();

                break;
        }

        // ensures that the statement was parsed completely
        if (!$tokenList->inEmbedded() && !$tokenList->isFinished()) {
            if (!$tokenList->has(TokenType::DELIMITER)) {
                $tokenList->expectSymbol(';');
            }
        }

        return $statement;
    }

    /**
     * @return Command&Statement
     */
    private function parseCommand(TokenList $tokenList, bool $topLevel): Command
    {
        $in = $tokenList->inRoutine();
        $statement = $this->parser->parseTokenList($tokenList);

        if ($statement instanceof InvalidCommand) {
            throw $statement->getException();
        } elseif ($statement instanceof ExplainForConnectionCommand) {
            throw new ParserException('Cannot use EXPLAIN FOR CONNECTION inside a routine.', $tokenList);
        } elseif ($statement instanceof LockTablesCommand || $statement instanceof UnlockTablesCommand) {
            throw new ParserException('Cannot use LOCK TABLES or UNLOCK TABLES inside a routine.', $tokenList);
        } elseif ($statement instanceof AlterEventCommand && $topLevel && $in === Routine::EVENT) {
            throw new ParserException('Cannot use ALTER EVENT inside ALTER EVENT directly. Use BEGIN/END block.', $tokenList);
        } elseif ($statement instanceof CreateEventCommand) {
            throw new ParserException('Cannot use CREATE EVENT inside an procedure.', $tokenList);
        } elseif ($statement instanceof AlterViewCommand) {
            throw new ParserException('Cannot use ALTER VIEW inside a routine.', $tokenList);
        } elseif ($statement instanceof LoadDataCommand || $statement instanceof LoadXmlCommand) {
            throw new ParserException('Cannot use LOAD DATA or LOAD XML inside a routine.', $tokenList);
        } elseif ($in !== Routine::PROCEDURE && $statement instanceof PreparedStatementCommand) {
            throw new ParserException('Cannot use prepared statements inside a function, trigger or event.', $tokenList);
        }

        return $statement;
    }

    private function parseBlock(TokenList $tokenList, ?string $label): CompoundStatement
    {
        $statements = $this->parseStatementList($tokenList);
        $tokenList->expectKeyword(Keyword::END);

        if ($label !== null) {
            $endLabel = $tokenList->getName(Entity::LABEL);
            if ($endLabel !== null && $endLabel !== $label) {
                $tokenList->missing($label);
            }
        }

        return new CompoundStatement($statements, $label);
    }

    /**
     * @return array<Statement>
     */
    private function parseStatementList(TokenList $tokenList): array
    {
        $statements = [];

        // for empty lists
        if ($tokenList->hasAnyKeyword(Keyword::END, Keyword::UNTIL, Keyword::WHEN, Keyword::ELSE, Keyword::ELSEIF)) {
            $tokenList->rewind(-1);
            return $statements;
        }

        $previous = $tokenList->inEmbedded();
        $tokenList->endEmbedded();
        do {
            $statements[] = $this->parseStatement($tokenList);

            // termination condition
            if ($tokenList->hasAnyKeyword(Keyword::END, Keyword::UNTIL, Keyword::WHEN, Keyword::ELSE, Keyword::ELSEIF)) {
                $tokenList->rewind(-1);
                break;
            }
        } while (!$tokenList->isFinished());
        $previous ? $tokenList->startEmbedded() : $tokenList->endEmbedded();

        return $statements;
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
            $endLabel = $tokenList->getName(Entity::LABEL);
            if ($endLabel !== null && $endLabel !== $label) {
                $tokenList->missing($label);
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
            $endLabel = $tokenList->getName(Entity::LABEL);
            if ($endLabel !== null && $endLabel !== $label) {
                $tokenList->missing($label);
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
        $tokenList->expectKeywords(Keyword::END, Keyword::WHILE);

        if ($label !== null) {
            $endLabel = $tokenList->getName(Entity::LABEL);
            if ($endLabel !== null && $endLabel !== $label) {
                $tokenList->missing($label);
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
        $values = [];
        /** @var non-empty-array<array<Statement>> $statementLists */
        $statementLists = [];
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
        $conditions = [];
        /** @var non-empty-array<array<Statement>> $statementLists */
        $statementLists = [];
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
                    $tokenList->passKeyword(Keyword::VALUE);
                    $value = $tokenList->getUnsignedInt();
                    if ($value === null) {
                        $value = $tokenList->expectNonReservedNameOrString();
                    }
                } else {
                    $value = $tokenList->getName(null);
                    if ($value !== null) {
                        $type = ConditionType::get(ConditionType::CONDITION);
                    } else {
                        $value = (int) $tokenList->expectUnsignedInt();
                        $type = ConditionType::get(ConditionType::ERROR);
                    }
                }
                $conditions[] = new Condition($type, $value);
            } while ($tokenList->hasSymbol(','));

            $previous = $tokenList->inEmbedded();
            $tokenList->startEmbedded();
            $statement = $this->parseStatement($tokenList);
            $previous ? $tokenList->startEmbedded() : $tokenList->endEmbedded();

            return new DeclareHandlerStatement($action, $conditions, $statement);
        }

        $name = $tokenList->expectNonReservedName(null);

        if ($tokenList->hasKeyword(Keyword::CURSOR)) {
            $tokenList->expectKeyword(Keyword::FOR);
            $query = $this->queryParser->parseQuery($tokenList);

            return new DeclareCursorStatement($name, $query);
        } elseif ($tokenList->hasKeyword(Keyword::CONDITION)) {
            $tokenList->expectKeywords(Keyword::FOR);
            if ($tokenList->hasKeyword(Keyword::SQLSTATE)) {
                $tokenList->passKeyword(Keyword::VALUE);
                $value = $tokenList->getUnsignedInt();
                if ($value === null) {
                    $value = $tokenList->expectNonReservedNameOrString();
                }
            } else {
                $value = (int) $tokenList->expectUnsignedInt();
            }

            return new DeclareConditionStatement($name, $value);
        }

        /** @var non-empty-array<string> $names */
        $names = [$name];
        while ($tokenList->hasSymbol(',')) {
            $names[] = $tokenList->expectNonReservedName(null);
        }
        $type = $this->expressionParser->parseColumnType($tokenList);
        $default = null;
        if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
            $default = $this->expressionParser->parseExpression($tokenList);
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
        $cursor = $tokenList->expectName(null);
        $tokenList->expectKeyword(Keyword::INTO);
        $variables = [];
        do {
            $variables[] = $tokenList->expectName(null);
        } while ($tokenList->hasSymbol(','));

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
    public function parseGetDiagnostics(TokenList $tokenList): GetDiagnosticsStatement
    {
        $tokenList->expectKeyword(Keyword::GET);

        $area = $tokenList->getKeywordEnum(DiagnosticsArea::class);
        $tokenList->expectKeyword(Keyword::DIAGNOSTICS);

        $statementItems = $conditionItems = $condition = null;
        if ($tokenList->hasKeyword(Keyword::CONDITION)) {
            $condition = $this->expressionParser->parseExpression($tokenList);
            if (($condition instanceof IntLiteral && !$condition instanceof UintLiteral) || (
                !$condition instanceof StringLiteral
                && !$condition instanceof NumberLiteral
                && !$condition instanceof SimpleName
                && !$condition instanceof UserVariable
                && !$condition instanceof NullLiteral
            )) {
                throw new ParserException('Only unsigned int, null or variable names is allowed as condition number.', $tokenList);
            }
            $conditionItems = [];
            do {
                $target = $this->parseTarget($tokenList);
                $tokenList->expectOperator(Operator::EQUAL);
                $item = $tokenList->expectKeywordEnum(ConditionInformationItem::class);
                $conditionItems[] = new DiagnosticsItem($target, $item);
            } while ($tokenList->hasSymbol(','));
        } else {
            $statementItems = [];
            do {
                $target = $this->parseTarget($tokenList);
                $tokenList->expectOperator(Operator::EQUAL);
                $item = $tokenList->expectKeywordEnum(StatementInformationItem::class);
                $statementItems[] = new DiagnosticsItem($target, $item);
            } while ($tokenList->hasSymbol(','));
        }

        return new GetDiagnosticsStatement($area, $statementItems, $condition, $conditionItems);
    }

    private function parseTarget(TokenList $tokenList): Identifier
    {
        if (($token = $tokenList->get(TokenType::AT_VARIABLE)) !== null) {
            $variable = $this->expressionParser->parseAtVariable($tokenList, $token->value);
            if (!$variable instanceof UserVariable) {
                throw new ParserException('User variable or local variable expected.', $tokenList);
            }

            return $variable;
        } else {
            $name = $tokenList->expectName(null);
            if ($tokenList->inRoutine() !== null) {
                // local variable
                return new SimpleName($name);
            } else {
                throw new ParserException('User variable or local variable expected.', $tokenList);
            }
        }
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
    public function parseSignalResignal(TokenList $tokenList)
    {
        $which = $tokenList->expectAnyKeyword(Keyword::SIGNAL, Keyword::RESIGNAL);

        if ($tokenList->hasKeyword(Keyword::SQLSTATE)) {
            $tokenList->passKeyword(Keyword::VALUE);
            $condition = $tokenList->expectString();
        } else {
            $condition = $tokenList->getNonReservedName(null);
        }
        $items = [];
        if ($tokenList->hasKeyword(Keyword::SET)) {
            do {
                $item = $tokenList->expectKeywordEnum(ConditionInformationItem::class)->getValue();
                $tokenList->expectOperator(Operator::EQUAL);
                $value = $this->expressionParser->parseExpression($tokenList);
                $items[$item] = $value;
            } while ($tokenList->hasSymbol(','));
        }

        if ($which === Keyword::SIGNAL) {
            return new SignalStatement($condition, $items);
        } else {
            return new ResignalStatement($condition, $items);
        }
    }

}
