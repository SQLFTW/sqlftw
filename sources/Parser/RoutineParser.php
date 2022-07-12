<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable PSR2.Methods.FunctionCallSignature.MultipleArguments

namespace SqlFtw\Parser;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\Dml\QueryParser;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Event\AlterEventCommand;
use SqlFtw\Sql\Ddl\Event\CreateEventCommand;
use SqlFtw\Sql\Ddl\View\AlterViewCommand;
use SqlFtw\Sql\Dml\Load\LoadDataCommand;
use SqlFtw\Sql\Dml\Load\LoadXmlCommand;
use SqlFtw\Sql\Dml\Prepared\PreparedStatementCommand;
use SqlFtw\Sql\Dml\Transaction\LockTablesCommand;
use SqlFtw\Sql\Dml\Transaction\UnlockTablesCommand;
use SqlFtw\Sql\Dml\Utility\ExplainForConnectionCommand;
use SqlFtw\Sql\Entity;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Routine\CaseStatement;
use SqlFtw\Sql\Routine\CloseCursorStatement;
use SqlFtw\Sql\Routine\CompoundStatement;
use SqlFtw\Sql\Routine\Condition;
use SqlFtw\Sql\Routine\ConditionType;
use SqlFtw\Sql\Routine\DeclareConditionStatement;
use SqlFtw\Sql\Routine\DeclareCursorStatement;
use SqlFtw\Sql\Routine\DeclareHandlerStatement;
use SqlFtw\Sql\Routine\DeclareStatement;
use SqlFtw\Sql\Routine\FetchStatement;
use SqlFtw\Sql\Routine\HandlerAction;
use SqlFtw\Sql\Routine\IfStatement;
use SqlFtw\Sql\Routine\IterateStatement;
use SqlFtw\Sql\Routine\LeaveStatement;
use SqlFtw\Sql\Routine\LoopStatement;
use SqlFtw\Sql\Routine\OpenCursorStatement;
use SqlFtw\Sql\Routine\RepeatStatement;
use SqlFtw\Sql\Routine\ReturnStatement;
use SqlFtw\Sql\Routine\Routine;
use SqlFtw\Sql\Routine\WhileStatement;
use SqlFtw\Sql\Statement;

class RoutineParser
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

        $in = $tokenList->inRoutine();

        if ($label !== null) {
            $keyword = $tokenList->expectAnyKeyword(Keyword::BEGIN, Keyword::LOOP, Keyword::REPEAT, Keyword::WHILE);
        } else {
            $keywords = [
                Keyword::BEGIN, Keyword::LOOP, Keyword::REPEAT, Keyword::WHILE, Keyword::CASE, Keyword::IF,
                Keyword::DECLARE, Keyword::OPEN, Keyword::FETCH, Keyword::CLOSE, Keyword::LEAVE, Keyword::ITERATE,
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

}
