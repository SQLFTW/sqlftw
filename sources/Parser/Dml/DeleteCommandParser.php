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
use SqlFtw\Sql\Dml\Delete\DeleteCommand;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Entity;

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
                throw new ParserException('WITH defined twice.', $tokenList);
            }

            /** @var DeleteCommand $command */
            $command = $this->withParser->parseWith($tokenList->rewind(-1));

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
                $tokenList->expectSymbol('(');
                $partitions = [];
                do {
                    $partitions[] = $tokenList->expectName(Entity::PARTITION);
                } while ($tokenList->hasSymbol(','));
                $tokenList->expectSymbol(')');
            }
        } else {
            $tables = $this->parseTablesList($tokenList);
            $tokenList->expectKeyword(Keyword::FROM);
            $references = $this->joinParser->parseTableReferences($tokenList);
        }

        $where = null;
        if ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseAssignExpression($tokenList);
        }

        $orderBy = $limit = null;
        if ($references === null) {
            if ($tokenList->hasKeywords(Keyword::ORDER, Keyword::BY)) {
                $orderBy = $this->expressionParser->parseOrderBy($tokenList);
            }
            if ($tokenList->hasKeyword(Keyword::LIMIT)) {
                $limit = $this->expressionParser->parseLimitOrOffsetValue($tokenList);
            }
        }

        return new DeleteCommand($tables, $where, $with, $orderBy, $limit, $references, $partitions, $lowPriority, $quick, $ignore);
    }

    /**
     * @return non-empty-array<array{QualifiedName, string|null}>
     */
    private function parseTablesList(TokenList $tokenList): array
    {
        $tables = [];
        do {
            $table = $tokenList->expectQualifiedName();
            if ($tokenList->hasSymbol('.')) {
                $tokenList->expectOperator(Operator::MULTIPLY);
            }
            $alias = $this->expressionParser->parseAlias($tokenList);

            $tables[] = [$table, $alias];
        } while ($tokenList->hasSymbol(','));

        return $tables;
    }

}
