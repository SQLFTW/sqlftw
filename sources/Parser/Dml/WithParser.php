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
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\Delete\DeleteCommand;
use SqlFtw\Sql\Dml\Query\SelectCommand;
use SqlFtw\Sql\Dml\Update\UpdateCommand;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Dml\WithExpression;
use SqlFtw\Sql\Keyword;

class WithParser
{
    use StrictBehaviorMixin;

    /** @var ParserFactory */
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
     * @return SelectCommand|UpdateCommand|DeleteCommand
     */
    public function parseWith(TokenList $tokenList): Command
    {
        $tokenList->expectKeyword(Keyword::WITH);
        $recursive = $tokenList->hasKeyword(Keyword::RECURSIVE);

        $expressions = [];
        do {
            $name = $tokenList->expectName();
            $columns = null;
            if ($tokenList->hasSymbol('(')) {
                $columns = [];
                do {
                    $columns[] = $tokenList->expectName();
                } while ($tokenList->hasSymbol(','));
                $tokenList->expectSymbol(')');
            }
            $tokenList->expectKeyword(Keyword::AS);
            $tokenList->expectSymbol('(');
            $query = $this->parserFactory->getQueryParser()->parseQuery($tokenList);
            $tokenList->expectSymbol(')');

            $expressions[] = new WithExpression($query, $name, $columns);
        } while ($tokenList->hasSymbol(','));

        $with = new WithClause($expressions, $recursive);

        if ($tokenList->hasSymbol('(')) {
            $next = '(';
        } else {
            $next = $tokenList->expectAnyKeyword(Keyword::SELECT, Keyword::UPDATE, Keyword::DELETE);
        }
        switch ($next) {
            case '(':
            case Keyword::SELECT:
                return $this->parserFactory->getQueryParser()->parseQuery($tokenList->resetPosition(-1), $with);
            case Keyword::UPDATE:
                return $this->parserFactory->getUpdateCommandParser()->parseUpdate($tokenList->resetPosition(-1), $with);
            case Keyword::DELETE:
                return $this->parserFactory->getDeleteCommandParser()->parseDelete($tokenList->resetPosition(-1), $with);
            default:
                throw new ShouldNotHappenException('');
        }
    }

}
