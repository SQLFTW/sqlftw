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
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Parser\JoinParser;
use SqlFtw\Sql\Dml\Delete\DeleteCommand;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\TableName;

class DeleteCommandParser
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
     * DELETE [LOW_PRIORITY] [QUICK] [IGNORE]
     *    FROM tbl_name
     *    [PARTITION (partition_name,...)]
     *    [WHERE where_condition]
     *    [ORDER BY ...]
     *    [LIMIT row_count]
     *
     * DELETE [LOW_PRIORITY] [QUICK] [IGNORE]
     *     tbl_name[.*] [, tbl_name[.*]] ...
     *     FROM table_references
     *     [WHERE where_condition]
     *
     * DELETE [LOW_PRIORITY] [QUICK] [IGNORE]
     *     FROM tbl_name[.*] [, tbl_name[.*]] ...
     *     USING table_references
     *     [WHERE where_condition]
     */
    public function parseDelete(TokenList $tokenList): DeleteCommand
    {
        $tokenList->consumeKeyword(Keyword::DELETE);
        $lowPriority = (bool) $tokenList->mayConsumeKeywords(Keyword::LOW_PRIORITY);
        $quick = (bool) $tokenList->mayConsumeKeyword(Keyword::QUICK);
        $ignore = (bool) $tokenList->mayConsumeKeyword(Keyword::IGNORE);

        $references = $partitions = null;
        if ($tokenList->mayConsumeKeyword(Keyword::FROM)) {
            $tables = $this->parseTablesList($tokenList);
            if ($tokenList->mayConsumeKeyword(Keyword::USING)) {
                $references = $this->joinParser->parseTableReferences($tokenList);
            } elseif ($tokenList->mayConsumeKeyword(Keyword::PARTITION)) {
                $tokenList->consume(TokenType::LEFT_PARENTHESIS);
                $partitions = [];
                do {
                    $partitions[] = $tokenList->consumeName();
                } while ($tokenList->mayConsumeComma());
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            }
        } else {
            $tables = $this->parseTablesList($tokenList);
            $tokenList->consumeKeyword(Keyword::FROM);
            $references = $this->joinParser->parseTableReferences($tokenList);
        }

        $where = null;
        if ($tokenList->mayConsumeKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        $orderBy = $limit = null;
        if ($references === null) {
            if ($tokenList->mayConsumeKeywords(Keyword::ORDER, Keyword::BY)) {
                $orderBy = $this->expressionParser->parseOrderBy($tokenList);
            }
            if ($tokenList->mayConsumeKeyword(Keyword::LIMIT)) {
                $limit = $tokenList->consumeInt();
            }
        }

        return new DeleteCommand($tables, $where, $orderBy, $limit, $references, $partitions, $lowPriority, $quick, $ignore);
    }

    private function parseTablesList(TokenList $tokenList): array
    {
        $tables = [];
        do {
            $tables[] = new TableName(...$tokenList->consumeQualifiedName());
            if ($tokenList->mayConsume(TokenType::DOT)) {
                $tokenList->consumeOperator(Operator::MULTIPLY);
            }
        } while ($tokenList->mayConsumeComma());

        return $tables;
    }

}
