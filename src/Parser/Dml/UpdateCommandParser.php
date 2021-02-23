<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use Countable;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\JoinParser;
use SqlFtw\Parser\ParserException;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dml\Update\SetColumnExpression;
use SqlFtw\Sql\Dml\Update\UpdateCommand;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;

class UpdateCommandParser
{
    use StrictBehaviorMixin;

    /** @var WithParser */
    private $withParser;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var JoinParser */
    private $joinParser;

    public function __construct(
        WithParser $withParser,
        ExpressionParser $expressionParser,
        JoinParser $joinParser
    ) {
        $this->withParser = $withParser;
        $this->expressionParser = $expressionParser;
        $this->joinParser = $joinParser;
    }

    /**
     * UPDATE [LOW_PRIORITY] [IGNORE] table_reference
     *     SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
     *     [WHERE where_condition]
     *     [ORDER BY ...]
     *     [LIMIT row_count]
     *
     * UPDATE [LOW_PRIORITY] [IGNORE] table_references
     *     SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
     *     [WHERE where_condition]
     *
     * @param TokenList $tokenList
     * @param WithClause|null $with
     * @return UpdateCommand
     */
    public function parseUpdate(TokenList $tokenList, ?WithClause $with = null): UpdateCommand
    {
        if ($tokenList->mayConsumeKeyword(Keyword::WITH)) {
            if ($with !== null) {
                throw new ParserException('WITH defined twice.');
            }

            return $this->withParser->parseWith($tokenList->resetPosition(-1));
        }

        $tokenList->consumeKeyword(Keyword::UPDATE);
        $lowPriority = (bool) $tokenList->mayConsumeKeyword(Keyword::LOW_PRIORITY);
        $ignore = (bool) $tokenList->mayConsumeKeyword(Keyword::IGNORE);

        $tableReferences = $this->joinParser->parseTableReferences($tokenList);

        $tokenList->consumeKeyword(Keyword::SET);
        $values = [];
        do {
            $column = $tokenList->consumeName();
            $tokenList->consumeOperator(Operator::EQUAL);
            if ($tokenList->mayConsumeKeyword(Keyword::DEFAULT)) {
                $values[$column] = new SetColumnExpression($column, null, true);
            } else {
                $value = $this->expressionParser->parseExpression($tokenList);
                $values[$column] = new SetColumnExpression($column, $value);
            }
        } while ($tokenList->mayConsumeComma());

        $where = null;
        if ($tokenList->mayConsumeKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        $orderBy = $limit = null;
        if (!$tableReferences instanceof Countable || $tableReferences->count() === 1) {
            if ($tokenList->mayConsumeKeywords(Keyword::ORDER, Keyword::BY)) {
                $orderBy = $this->expressionParser->parseOrderBy($tokenList);
            }
            if ($tokenList->mayConsumeKeyword(Keyword::LIMIT)) {
                $limit = $tokenList->consumeInt();
            }
        }
        $tokenList->expectEnd();

        return new UpdateCommand($tableReferences, $values, $where, $with, $orderBy, $limit, $ignore, $lowPriority);
    }

}
