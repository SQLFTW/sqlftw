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
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Ddl\Routines\AlterFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\AlterProcedureCommand;
use SqlFtw\Sql\Ddl\Routines\CreateFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\CreateProcedureCommand;
use SqlFtw\Sql\Ddl\Routines\DropFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\DropProcedureCommand;
use SqlFtw\Sql\Ddl\Routines\InOutParamFlag;
use SqlFtw\Sql\Ddl\Routines\ProcedureParam;
use SqlFtw\Sql\Ddl\Routines\RoutineSideEffects;
use SqlFtw\Sql\Ddl\SqlSecurity;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class RoutineCommandsParser
{
    use StrictBehaviorMixin;

    /** @var TypeParser */
    private $typeParser;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var CompoundStatementParser */
    private $compoundStatementParser;

    public function __construct(
        TypeParser $typeParser,
        ExpressionParser $expressionParser,
        CompoundStatementParser $compoundStatementParser
    ) {
        $this->typeParser = $typeParser;
        $this->expressionParser = $expressionParser;
        $this->compoundStatementParser = $compoundStatementParser;
    }

    /**
     * ALTER FUNCTION func_name [characteristic ...]
     *
     * characteristic:
     *     COMMENT 'string'
     *   | LANGUAGE SQL
     *   | { CONTAINS SQL | NO SQL | READS SQL DATA | MODIFIES SQL DATA }
     *   | SQL SECURITY { DEFINER | INVOKER }
     *
     * @param TokenList $tokenList
     * @return AlterFunctionCommand
     */
    public function parseAlterFunction(TokenList $tokenList): AlterFunctionCommand
    {
        $tokenList->consumeKeywords(Keyword::ALTER, Keyword::FUNCTION);
        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

        [$comment, $language, $sideEffects, $sqlSecurity] = $this->parseRoutineCharacteristics($tokenList, false);
        $tokenList->expectEnd();

        return new AlterFunctionCommand($name, $sqlSecurity, $sideEffects, $comment, $language);
    }

    /**
     * ALTER PROCEDURE proc_name [characteristic ...]
     *
     * characteristic:
     *     COMMENT 'string'
     *   | LANGUAGE SQL
     *   | { CONTAINS SQL | NO SQL | READS SQL DATA | MODIFIES SQL DATA }
     *   | SQL SECURITY { DEFINER | INVOKER }
     *
     * @param TokenList $tokenList
     * @return AlterProcedureCommand
     */
    public function parseAlterProcedure(TokenList $tokenList): AlterProcedureCommand
    {
        $tokenList->consumeKeywords(Keyword::ALTER, Keyword::PROCEDURE);
        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

        [$comment, $language, $sideEffects, $sqlSecurity] = $this->parseRoutineCharacteristics($tokenList, false);
        $tokenList->expectEnd();

        return new AlterProcedureCommand($name, $sqlSecurity, $sideEffects, $comment, $language);
    }

    /**
     * @param TokenList $tokenList
     * @param bool $procedure
     * @return mixed[]
     */
    private function parseRoutineCharacteristics(TokenList $tokenList, bool $procedure = false): array
    {
        $comment = $language = $sideEffects = $sqlSecurity = $deterministic = null;

        $keywords = [Keyword::COMMENT, Keyword::LANGUAGE, Keyword::CONTAINS, Keyword::NO, Keyword::READS, Keyword::MODIFIES, Keyword::SQL];
        if ($procedure) {
            $keywords[] = Keyword::NOT;
            $keywords[] = Keyword::DETERMINISTIC;
        }

        while ($keyword = $tokenList->mayConsumeAnyKeyword(...$keywords)) {
            if ($keyword === Keyword::COMMENT) {
                $comment = $tokenList->consumeString();
            } elseif ($keyword === Keyword::LANGUAGE) {
                $language = $tokenList->consumeKeyword(Keyword::SQL);
            } elseif ($keyword === Keyword::CONTAINS) {
                $tokenList->consumeKeyword(Keyword::SQL);
                $sideEffects = RoutineSideEffects::get(RoutineSideEffects::CONTAINS_SQL);
            } elseif ($keyword === Keyword::NO) {
                $tokenList->consumeKeyword(Keyword::SQL);
                $sideEffects = RoutineSideEffects::get(RoutineSideEffects::NO_SQL);
            } elseif ($keyword === Keyword::READS) {
                $tokenList->consumeKeywords(Keyword::SQL, Keyword::DATA);
                $sideEffects = RoutineSideEffects::get(RoutineSideEffects::READS_SQL_DATA);
            } elseif ($keyword === Keyword::MODIFIES) {
                $tokenList->consumeKeywords(Keyword::SQL, Keyword::DATA);
                $sideEffects = RoutineSideEffects::get(RoutineSideEffects::MODIFIES_SQL_DATA);
            } elseif ($keyword === Keyword::SQL) {
                $tokenList->consumeKeyword(Keyword::SECURITY);
                /** @var SqlSecurity $sqlSecurity */
                $sqlSecurity = $tokenList->consumeKeywordEnum(SqlSecurity::class);
            } elseif ($keyword === Keyword::NOT) {
                $tokenList->consumeKeyword(Keyword::DETERMINISTIC);
                $deterministic = false;
            } elseif ($keyword === Keyword::DETERMINISTIC) {
                $deterministic = true;
            }
        }

        return [$comment, $language, $sideEffects, $sqlSecurity, $deterministic];
    }

    /**
     * CREATE
     *   [DEFINER = { user | CURRENT_USER }]
     *   FUNCTION sp_name ([func_parameter[, ...]])
     *   RETURNS type
     *   [characteristic ...] routine_body
     *
     * func_parameter:
     *   param_name type
     *
     * type:
     *   Any valid MySQL data type
     *
     * characteristic:
     *     COMMENT 'string'
     *   | LANGUAGE SQL
     *   | [NOT] DETERMINISTIC
     *   | { CONTAINS SQL | NO SQL | READS SQL DATA | MODIFIES SQL DATA }
     *   | SQL SECURITY { DEFINER | INVOKER }
     *
     * routine_body:
     *   Valid SQL routine statement
     *
     * @param TokenList $tokenList
     * @return CreateFunctionCommand
     */
    public function parseCreateFunction(TokenList $tokenList): CreateFunctionCommand
    {
        $tokenList->consumeKeyword(Keyword::CREATE);
        $definer = null;
        if ($tokenList->mayConsumeKeyword(Keyword::DEFINER)) {
            $tokenList->consumeOperator(Operator::EQUAL);
            $definer = $this->expressionParser->parseUserExpression($tokenList);
        }
        $tokenList->consumeKeyword(Keyword::FUNCTION);
        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

        $params = [];
        $tokenList->consume(TokenType::LEFT_PARENTHESIS);
        if (!$tokenList->mayConsume(TokenType::RIGHT_PARENTHESIS)) {
            do {
                $param = $tokenList->consumeName();
                $type = $this->typeParser->parseType($tokenList);
                $params[$param] = $type;
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }

        $tokenList->consumeKeyword(Keyword::RETURNS);
        $returnType = $this->typeParser->parseType($tokenList);

        [$comment, $language, $sideEffects, $sqlSecurity, $deterministic] = $this->parseRoutineCharacteristics($tokenList, true);

        $body = $this->compoundStatementParser->parseCompoundStatement($tokenList);
        $tokenList->expectEnd();

        return new CreateFunctionCommand($name, $body, $params, $returnType, $definer, $deterministic, $sqlSecurity, $sideEffects, $comment, $language);
    }

    /**
     * CREATE
     *     [DEFINER = { user | CURRENT_USER }]
     *     PROCEDURE sp_name ([proc_parameter[, ...]])
     *     [characteristic ...] routine_body
     *
     * proc_parameter:
     *     [ IN | OUT | INOUT ] param_name type
     *
     * type:
     *     Any valid MySQL data type
     *
     * characteristic:
     *     COMMENT 'string'
     *   | LANGUAGE SQL
     *   | [NOT] DETERMINISTIC
     *   | { CONTAINS SQL | NO SQL | READS SQL DATA | MODIFIES SQL DATA }
     *   | SQL SECURITY { DEFINER | INVOKER }
     *
     * routine_body:
     *     Valid SQL routine statement
     *
     * @param TokenList $tokenList
     * @return CreateProcedureCommand
     */
    public function parseCreateProcedure(TokenList $tokenList): CreateProcedureCommand
    {
        $tokenList->consumeKeyword(Keyword::CREATE);
        $definer = null;
        if ($tokenList->mayConsumeKeyword(Keyword::DEFINER)) {
            $tokenList->consumeOperator(Operator::EQUAL);
            $definer = $this->expressionParser->parseUserExpression($tokenList);
        }
        $tokenList->consumeKeyword(Keyword::PROCEDURE);
        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

        $params = [];
        $tokenList->consume(TokenType::LEFT_PARENTHESIS);
        if (!$tokenList->mayConsume(TokenType::RIGHT_PARENTHESIS)) {
            do {
                /** @var InOutParamFlag $inOut */
                $inOut = $tokenList->mayConsumeKeywordEnum(InOutParamFlag::class);
                $param = $tokenList->consumeName();
                $type = $this->typeParser->parseType($tokenList);
                $params[] = new ProcedureParam($param, $type, $inOut);
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }

        [$comment, $language, $sideEffects, $sqlSecurity, $deterministic] = $this->parseRoutineCharacteristics($tokenList, true);

        $body = $this->compoundStatementParser->parseCompoundStatement($tokenList);
        $tokenList->expectEnd();

        return new CreateProcedureCommand($name, $body, $params, $definer, $deterministic, $sqlSecurity, $sideEffects, $comment, $language);
    }

    /**
     * DROP FUNCTION [IF EXISTS] sp_name
     *
     * @param TokenList $tokenList
     * @return DropFunctionCommand
     */
    public function parseDropFunction(TokenList $tokenList): DropFunctionCommand
    {
        $tokenList->consumeKeywords(Keyword::DROP, Keyword::FUNCTION);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);
        $name = new QualifiedName(...$tokenList->consumeQualifiedName());
        $tokenList->expectEnd();

        return new DropFunctionCommand($name, $ifExists);
    }

    /**
     * DROP PROCEDURE [IF EXISTS] sp_name
     *
     * @param TokenList $tokenList
     * @return DropProcedureCommand
     */
    public function parseDropProcedure(TokenList $tokenList): DropProcedureCommand
    {
        $tokenList->consumeKeywords(Keyword::DROP, Keyword::PROCEDURE);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);
        $name = new QualifiedName(...$tokenList->consumeQualifiedName());
        $tokenList->expectEnd();

        return new DropProcedureCommand($name, $ifExists);
    }

}
