<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\JoinParser;
use SqlFtw\Parser\ParserException;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dml\Delete\DeleteCommand;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class DeleteCommandParser
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
     * DELETE [LOW_PRIORITY] [QUICK] [IGNORE]
     *    FROM tbl_name
     *    [PARTITION (partition_name, ...)]
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
    public function parseDelete(TokenList $tokenList, ?WithClause $with = null): DeleteCommand
    {
        if ($tokenList->hasKeyword(Keyword::WITH)) {
            if ($with !== null) {
                throw new ParserException('WITH defined twice.');
            }

            /** @var DeleteCommand $command */
            $command = $this->withParser->parseWith($tokenList->resetPosition(-1));

            return $command;
        }

        $tokenList->expectKeyword(Keyword::DELETE);
        $lowPriority = $tokenList->hasKeywords(Keyword::LOW_PRIORITY);
        $quick = $tokenList->hasKeyword(Keyword::QUICK);
        $ignore = $tokenList->hasKeyword(Keyword::IGNORE);

        $references = $partitions = null;
        if ($tokenList->hasKeyword(Keyword::FROM)) {
            $tables = $this->parseTablesList($tokenList);
            if ($tokenList->hasKeyword(Keyword::USING)) {
                $references = $this->joinParser->parseTableReferences($tokenList);
            } elseif ($tokenList->hasKeyword(Keyword::PARTITION)) {
                $tokenList->expect(TokenType::LEFT_PARENTHESIS);
                $partitions = [];
                do {
                    $partitions[] = $tokenList->expectName();
                } while ($tokenList->hasComma());
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            }
        } else {
            $tables = $this->parseTablesList($tokenList);
            $tokenList->expectKeyword(Keyword::FROM);
            $references = $this->joinParser->parseTableReferences($tokenList);
        }

        $where = null;
        if ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        $orderBy = $limit = null;
        if ($references === null) {
            if ($tokenList->hasKeywords(Keyword::ORDER, Keyword::BY)) {
                $orderBy = $this->expressionParser->parseOrderBy($tokenList);
            }
            if ($tokenList->hasKeyword(Keyword::LIMIT)) {
                $limit = $tokenList->expectInt();
            }
        }

        return new DeleteCommand($tables, $where, $with, $orderBy, $limit, $references, $partitions, $lowPriority, $quick, $ignore);
    }

    /**
     * @return QualifiedName[]
     */
    private function parseTablesList(TokenList $tokenList): array
    {
        $tables = [];
        do {
            $tables[] = new QualifiedName(...$tokenList->expectQualifiedName());
            if ($tokenList->has(TokenType::DOT)) {
                $tokenList->expectOperator(Operator::MULTIPLY);
            }
        } while ($tokenList->hasComma());

        return $tables;
    }

}
