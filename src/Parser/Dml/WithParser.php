<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ParserFactory;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Dml\WithExpression;
use SqlFtw\Sql\Keyword;

class WithParser
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\ParserFactory */
    private $parserFactory;

    public function __construct(ParserFactory $parserFactory)
    {
        $this->parserFactory = $parserFactory;
    }

    /**
     * with_clause:
     *   WITH [RECURSIVE]
     *     cte_name [(col_name [, col_name] ...)] AS (subquery)
     *     [, cte_name [(col_name [, col_name] ...)] AS (subquery)] ...
     *
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Dml\Select\SelectCommand|\SqlFtw\Sql\Dml\Update\UpdateCommand|\SqlFtw\Sql\Dml\Delete\DeleteCommand
     */
    public function parseWith(TokenList $tokenList): Command
    {
        $tokenList->consumeKeyword(Keyword::WITH);
        $recursive = (bool) $tokenList->mayConsumeKeyword(Keyword::RECURSIVE);

        $expressions = [];
        do {
            $name = $tokenList->consumeName();
            $columns = null;
            if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
                $columns = [];
                do {
                    $columns[] = $tokenList->consumeName();
                } while ($tokenList->mayConsumeComma());
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            }
            $tokenList->consumeKeyword(Keyword::AS);
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $query = $this->parserFactory->getSelectCommandParser()->parseSelect($tokenList);
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

            $expressions[] = new WithExpression($query, $name, $columns);
        } while ($tokenList->mayConsumeComma());

        $with = new WithClause($expressions, $recursive);

        $next = $tokenList->consumeAnyKeyword(Keyword::SELECT, Keyword::UPDATE, Keyword::DELETE);
        switch ($next) {
            case Keyword::SELECT:
                return $this->parserFactory->getSelectCommandParser()->parseSelect($tokenList->resetPosition(-1), $with);
            case Keyword::UPDATE:
                return $this->parserFactory->getUpdateCommandParser()->parseUpdate($tokenList->resetPosition(-1), $with);
            case Keyword::DELETE:
                return $this->parserFactory->getDeleteCommandParser()->parseDelete($tokenList->resetPosition(-1), $with);
            default:
                throw new ShouldNotHappenException('');
        }
    }

}
