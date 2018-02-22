<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\JoinParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dml\Update\UpdateCommand;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;

class UpdateCommandParser
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\ExpressionParser */
    private $expressionParser;

    /** @var \SqlFtw\Parser\JoinParser */
    private $joinParser;

    public function __construct(ExpressionParser $expressionParser, JoinParser $joinParser)
    {
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
     */
    public function parseUpdate(TokenList $tokenList): UpdateCommand
    {
        $tokenList->consumeKeyword(Keyword::UPDATE);
        $lowPriority = (bool) $tokenList->mayConsumeKeyword(Keyword::LOW_PRIORITY);
        $ignore = (bool) $tokenList->mayConsumeKeyword(Keyword::IGNORE);

        $tableReferences = $this->joinParser->parseTableReferences($tokenList);

        $tokenList->consumeKeyword(Keyword::SET);
        $values = [];
        do {
            $column = $tokenList->consumeName();
            $tokenList->consumeOperator(Operator::EQUAL);
            $value = $this->expressionParser->parseExpression($tokenList);
            $values[$column] = $value;
        } while ($tokenList->mayConsumeComma());

        $where = null;
        if ($tokenList->mayConsumeKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        $orderBy = $limit = null;
        if (!$tableReferences instanceof \Countable || $tableReferences->count() === 1) {
            if ($tokenList->mayConsumeKeywords(Keyword::ORDER, Keyword::BY)) {
                $orderBy = $this->expressionParser->parseOrderBy($orderBy);
            }
            if ($tokenList->mayConsumeKeyword(Keyword::LIMIT)) {
                $limit = $tokenList->consumeInt();
            }
        }

        return new UpdateCommand($tableReferences, $values, $where, $orderBy, $limit, $ignore, $lowPriority);
    }

}
