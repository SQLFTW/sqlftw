<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable SlevomatCodingStandard.ControlStructures.AssignmentInCondition

namespace SqlFtw\Parser\Dal;

use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dal\Set\ResetPersistCommand;
use SqlFtw\Sql\Dal\Set\SetCharacterSetCommand;
use SqlFtw\Sql\Dal\Set\SetNamesCommand;
use SqlFtw\Sql\Dal\Set\SetVariablesCommand;
use SqlFtw\Sql\EntityType;
use SqlFtw\Sql\Expression\DefaultLiteral;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\MysqlVariable;

class SetCommandsParser
{

    private ExpressionParser $expressionParser;

    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    /**
     * SET variable_assignment [, variable_assignment] ...
     */
    public function parseSet(TokenList $tokenList): SetVariablesCommand
    {
        $tokenList->expectKeyword(Keyword::SET);

        $assignments = $this->expressionParser->parseSetAssignments($tokenList);

        return new SetVariablesCommand($assignments);
    }

    /**
     * SET {CHARACTER SET | CHAR SET | CHARSET}
     *     {'charset_name' | DEFAULT}
     */
    public function parseSetCharacterSet(TokenList $tokenList): SetCharacterSetCommand
    {
        $tokenList->expectKeyword(Keyword::SET);
        $keyword = $tokenList->expectAnyKeyword(Keyword::CHARACTER, Keyword::CHAR, Keyword::CHARSET);
        if ($keyword !== Keyword::CHARSET) {
            $tokenList->expectKeyword(Keyword::SET);
        }

        if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
            $charset = null;
        } else {
            $charset = $tokenList->expectCharsetName();
        }

        $assignments = [];
        if ($tokenList->hasSymbol(',')) {
            $assignments = $this->expressionParser->parseSetAssignments($tokenList);
        }

        return new SetCharacterSetCommand($charset, $assignments);
    }

    /**
     * SET NAMES {'charset_name' [COLLATE 'collation_name'] | DEFAULT}
     *     [, variable_name [=] value]
     */
    public function parseSetNames(TokenList $tokenList): SetNamesCommand
    {
        $tokenList->expectKeywords(Keyword::SET, Keyword::NAMES);

        $collation = null;
        if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
            $charset = new DefaultLiteral();
        } else {
            $charset = $tokenList->expectCharsetName();

            if ($tokenList->hasKeyword(Keyword::COLLATE)) {
                $collation = $tokenList->expectCollationName();
            }
        }

        $assignments = [];
        if ($tokenList->hasSymbol(',')) {
            $assignments = $this->expressionParser->parseSetAssignments($tokenList);
        }

        return new SetNamesCommand($charset, $collation, $assignments);
    }

    /**
     * RESET PERSIST [[IF EXISTS] system_var_name]
     */
    public function parseResetPersist(TokenList $tokenList): ResetPersistCommand
    {
        $tokenList->expectKeywords(Keyword::RESET, Keyword::PERSIST);
        $ifExists = $tokenList->hasKeywords(Keyword::IF, Keyword::EXISTS);
        if ($ifExists) {
            $variable = $tokenList->expectName(EntityType::SYSTEM_VARIABLE);
        } else {
            $variable = $tokenList->getName(EntityType::SYSTEM_VARIABLE);
        }
        if ($tokenList->hasSymbol('.')) {
            $variable .= '.' . $tokenList->expectName(EntityType::SYSTEM_VARIABLE);
        }
        if ($variable !== null) {
            $variable = new MysqlVariable($variable);
        }

        return new ResetPersistCommand($variable, $ifExists);
    }

}
