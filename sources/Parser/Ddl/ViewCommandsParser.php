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
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Ddl\SqlSecurity;
use SqlFtw\Sql\Ddl\View\AlterViewCommand;
use SqlFtw\Sql\Ddl\View\CreateViewCommand;
use SqlFtw\Sql\Ddl\View\DropViewCommand;
use SqlFtw\Sql\Ddl\View\DropViewOption;
use SqlFtw\Sql\Ddl\View\ViewAlgorithm;
use SqlFtw\Sql\Ddl\View\ViewCheckOption;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class ViewCommandsParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var SelectCommandParser */
    private $selectCommandParser;

    public function __construct(
        ExpressionParser $expressionParser,
        SelectCommandParser $selectCommandParser
    ) {
        $this->expressionParser = $expressionParser;
        $this->selectCommandParser = $selectCommandParser;
    }

    /**
     * ALTER
     *     [ALGORITHM = {UNDEFINED | MERGE | TEMPTABLE}]
     *     [DEFINER = { user | CURRENT_USER }]
     *     [SQL SECURITY { DEFINER | INVOKER }]
     *     VIEW view_name [(column_list)]
     *     AS select_statement
     *     [WITH [CASCADED | LOCAL] CHECK OPTION]
     */
    public function parseAlterView(TokenList $tokenList): AlterViewCommand
    {
        $tokenList->expectKeyword(Keyword::ALTER);
        $params = $this->parseViewDefinition($tokenList);
        $tokenList->expectEnd();

        return new AlterViewCommand(...$params);
    }

    /**
     * CREATE
     *     [OR REPLACE]
     *     [ALGORITHM = {UNDEFINED | MERGE | TEMPTABLE}]
     *     [DEFINER = { user | CURRENT_USER }]
     *     [SQL SECURITY { DEFINER | INVOKER }]
     *     VIEW view_name [(column_list)]
     *     AS select_statement
     *     [WITH [CASCADED | LOCAL] CHECK OPTION]
     */
    public function parseCreateView(TokenList $tokenList): CreateViewCommand
    {
        $tokenList->expectKeyword(Keyword::CREATE);
        $orReplace = $tokenList->hasKeywords(Keyword::OR, Keyword::REPLACE);

        $params = $this->parseViewDefinition($tokenList) + [$orReplace];
        $params[] = $orReplace;
        $tokenList->expectEnd();

        return new CreateViewCommand(...$params);
    }

    /**
     * @return mixed[]
     */
    private function parseViewDefinition(TokenList $tokenList): array
    {
        $algorithm = $definer = $sqlSecurity = $checkOption = null;
        if ($tokenList->hasKeyword(Keyword::ALGORITHM)) {
            $tokenList->expectOperator(Operator::EQUAL);
            /** @var ViewAlgorithm $algorithm */
            $algorithm = $tokenList->expectKeywordEnum(ViewAlgorithm::class);
        }
        if ($tokenList->hasKeyword(Keyword::DEFINER)) {
            $tokenList->expectOperator(Operator::EQUAL);
            $definer = $this->expressionParser->parseUserExpression($tokenList);
        }
        if ($tokenList->hasKeyword(Keyword::SQL)) {
            $tokenList->expectKeyword(Keyword::SECURITY);
            /** @var SqlSecurity $sqlSecurity */
            $sqlSecurity = $tokenList->expectKeywordEnum(SqlSecurity::class);
        }

        $tokenList->expectKeyword(Keyword::VIEW);
        $name = new QualifiedName(...$tokenList->expectQualifiedName());

        $columns = null;
        if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
            $columns = [];
            do {
                $columns[] = $tokenList->expectName();
            } while ($tokenList->hasComma());

            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
        }

        $tokenList->expectKeyword(Keyword::AS);
        $body = $this->selectCommandParser->parseSelect($tokenList);

        if ($tokenList->hasKeyword(Keyword::WITH)) {
            $option = $tokenList->getAnyKeyword(Keyword::CASCADED, Keyword::LOCAL);
            if ($option !== null) {
                $checkOption = ViewCheckOption::get($option . ' CHECK OPTION');
            } else {
                $checkOption = ViewCheckOption::get(ViewCheckOption::CHECK_OPTION);
            }
            $tokenList->expectKeywords(Keyword::CHECK, Keyword::OPTION);
        }

        return [$name, $body, $columns, $definer, $sqlSecurity, $algorithm, $checkOption];
    }

    /**
     * DROP VIEW [IF EXISTS]
     *     view_name [, view_name] ...
     *     [RESTRICT | CASCADE]
     */
    public function parseDropView(TokenList $tokenList): DropViewCommand
    {
        $tokenList->expectKeywords(Keyword::DROP, Keyword::VIEW);
        $ifExists = $tokenList->hasKeywords(Keyword::IF, Keyword::EXISTS);

        $names = [];
        do {
            $names[] = new QualifiedName(...$tokenList->expectQualifiedName());
        } while ($tokenList->hasComma());

        $option = $tokenList->getAnyKeyword(Keyword::RESTRICT, Keyword::CASCADE);
        if ($option !== null) {
            $option = DropViewOption::get($option);
        }
        $tokenList->expectEnd();

        return new DropViewCommand($names, $ifExists, $option);
    }

}
