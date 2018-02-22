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
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dml\Select\GroupByExpression;
use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\Sql\Dml\Select\SelectDistinctOption;
use SqlFtw\Sql\Dml\Select\SelectExpression;
use SqlFtw\Sql\Dml\Select\SelectInto;
use SqlFtw\Sql\Dml\Select\SelectLocking;
use SqlFtw\Sql\Dml\Select\SelectLockOption;
use SqlFtw\Sql\Dml\Select\SelectLockWaitOption;
use SqlFtw\Sql\Dml\Select\SelectOption;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Order;
use SqlFtw\Sql\TableName;

class SelectCommandParser
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\ExpressionParser */
    private $expressionParser;

    /** @var \SqlFtw\Parser\JoinParser */
    private $joinParser;

    /** @var \SqlFtw\Parser\Dml\FileFormatParser */
    private $fileFormatParser;

    public function __construct(
        ExpressionParser $expressionParser,
        JoinParser $joinParser,
        FileFormatParser $fileFormatParser
    ) {
        $this->expressionParser = $expressionParser;
        $this->joinParser = $joinParser;
        $this->fileFormatParser = $fileFormatParser;
    }

    /**
     * SELECT
     *     [ALL | DISTINCT | DISTINCTROW ]
     *     [HIGH_PRIORITY]
     *     [STRAIGHT_JOIN]
     *     [SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
     *     [SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
     *     select_expr [, select_expr ...]
     *     [FROM table_references
     *       [PARTITION partition_list]
     *     [WHERE where_condition]
     *     [GROUP BY {col_name | expr | position}
     *       [ASC | DESC], ... [WITH ROLLUP]]
     *     [HAVING where_condition]
     *     [ORDER BY {col_name | expr | position}
     *       [ASC | DESC], ...]
     *     [LIMIT {[offset,] row_count | row_count OFFSET offset}]
     *     [INTO OUTFILE 'file_name'
     *         [CHARACTER SET charset_name]
     *         export_options
     *       | INTO DUMPFILE 'file_name'
     *       | INTO var_name [, var_name]]
     *     [FOR UPDATE | LOCK IN SHARE MODE]]
     *     [FOR {UPDATE | SHARE} [OF tbl_name [, tbl_name] ...] [NOWAIT | SKIP LOCKED]
     *       | LOCK IN SHARE MODE]]
     */
    public function parseSelect(TokenList $tokenList): SelectCommand
    {
        $tokenList->consumeKeyword(Keyword::SELECT);

        /** @var \SqlFtw\Sql\Dml\Select\SelectDistinctOption $distinct */
        $distinct = $tokenList->mayConsumeKeywordEnum(SelectDistinctOption::class);
        $options = [];
        $options[SelectOption::HIGH_PRIORITY] = (bool) $tokenList->mayConsumeKeyword(Keyword::HIGH_PRIORITY);
        $options[SelectOption::STRAIGHT_JOIN] = (bool) $tokenList->mayConsumeKeyword(Keyword::STRAIGHT_JOIN);
        $options[SelectOption::SMALL_RESULT] = (bool) $tokenList->mayConsumeKeyword(Keyword::SQL_SMALL_RESULT);
        $options[SelectOption::BIG_RESULT] = (bool) $tokenList->mayConsumeKeyword(Keyword::SQL_BIG_RESULT);
        $options[SelectOption::BUFFER_RESULT] = (bool) $tokenList->mayConsumeKeyword(Keyword::SQL_BUFFER_RESULT);
        $options[SelectOption::CACHE] = (bool) $tokenList->mayConsumeKeyword(Keyword::SQL_CACHE);
        $options[SelectOption::NO_CACHE] = (bool) $tokenList->mayConsumeKeyword(Keyword::SQL_NO_CACHE);
        $options[SelectOption::CALC_FOUND_ROWS] = (bool) $tokenList->mayConsumeKeyword(Keyword::SQL_CALC_FOUND_ROWS);

        $what = [];
        do {
            $value = $this->expressionParser->parseExpression($tokenList);
            if ($tokenList->mayConsumeKeyword(Keyword::AS)) {
                $alias = $tokenList->consumeName();
            } else {
                $alias = $tokenList->mayConsumeName();
            }
            $what[] = new SelectExpression($value, $alias);
        } while ($tokenList->mayConsumeComma());

        $from = $partitions = null;
        if ($tokenList->mayConsumeKeyword(Keyword::FROM)) {
            $from = $this->joinParser->parseTableReferences($tokenList);
            /*
            // todo: should be part of the table references or not?
            if ($tokenList->mayConsumeKeyword(Keyword::PARTITION)) {
                $partitions = [];
                do {
                    $partitions[] = $tokenList->consumeName();
                } while ($tokenList->mayConsumeComma());
            }
            */
        }

        $where = null;
        if ($tokenList->mayConsumeKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        $groupBy = $withRollup = null;
        if ($tokenList->mayConsumeKeywords(Keyword::GROUP, Keyword::BY)) {
            $groupBy = [];
            do {
                $expression = $this->expressionParser->parseExpression($tokenList);
                /** @var \SqlFtw\Sql\Order $order */
                $order = $tokenList->mayConsumeKeywordEnum(Order::class);
                $groupBy[] = new GroupByExpression($expression, $order);
            } while ($tokenList->mayConsumeComma());

            $withRollup = (bool) $tokenList->mayConsumeKeywords(Keyword::WITH, Keyword::ROLLUP);
        }

        $having = null;
        if ($tokenList->mayConsumeKeyword(Keyword::HAVING)) {
            $having = $this->expressionParser->parseExpression($tokenList);
        }

        $orderBy = null;
        if ($tokenList->mayConsumeKeywords(Keyword::ORDER, Keyword::BY)) {
            $orderBy = $this->expressionParser->parseOrderBy($tokenList);
        }

        $limit = $offset = null;
        if ($tokenList->mayConsumeKeyword(Keyword::LIMIT)) {
            [$limit, $offset] = $this->expressionParser->parseLimitAndOffset($tokenList);
        }

        $into = $outFile = $charset = $format = $dumpFile = $variables = null;
        if ($tokenList->mayConsumeKeywords(Keyword::INTO, Keyword::OUTFILE)) {
            $outFile = $tokenList->consumeString();
            if ($tokenList->mayConsumeKeywords(Keyword::CHARACTER, Keyword::SET)) {
                $charset = $tokenList->consumeName();
            }
            $format = $this->fileFormatParser->parseFormat($tokenList);
            $into = new SelectInto(null, null, $outFile, $charset, $format);
        } elseif ($tokenList->mayConsumeKeywords(Keyword::INTO, Keyword::DUMPFILE)) {
            $dumpFile = $tokenList->consumeString();
            $into = new SelectInto(null, $dumpFile);
        } elseif ($tokenList->mayConsumeKeyword(Keyword::INTO)) {
            $variables = [];
            do {
                $variables[] = $tokenList->consume(TokenType::AT_VARIABLE)->value;
            } while ($tokenList->mayConsumeComma());
            $into = new SelectInto($variables);
        }

        $locking = $lockOption = $lockTables = $lockWaitOption = null;
        if ($tokenList->mayConsumeKeywords(Keyword::LOCK, Keyword::IN, Keyword::SHARE, Keyword::MODE)) {
            $lockOption = SelectLockOption::get(SelectLockOption::FOR_SHARE);
            $locking = new SelectLocking($lockOption);
        } elseif ($tokenList->mayConsumeKeyword(Keyword::FOR)) {
            if ($tokenList->mayConsumeKeyword(Keyword::UPDATE)) {
                $lockOption = SelectLockOption::get(SelectLockOption::FOR_UPDATE);
            } else {
                $tokenList->consumeKeyword(Keyword::SHARE);
                $lockOption = SelectLockOption::get(SelectLockOption::FOR_SHARE);
            }
            if ($tokenList->mayConsumeKeyword(Keyword::OF)) {
                $lockTables = [];
                do {
                    $lockTables[] = new TableName(...$tokenList->consumeName());
                } while ($tokenList->mayConsumeComma());
            }
            /** @var \SqlFtw\Sql\Dml\Select\SelectLockWaitOption $lockWaitOption */
            $lockWaitOption = $tokenList->mayConsumeKeywordEnum(SelectLockWaitOption::class);
            $locking = new SelectLocking($lockOption, $lockWaitOption, $lockTables);
        }

        return new SelectCommand(
            $what, $from, $where, $groupBy, $having, $orderBy, $limit, $offset,
            $distinct, $options, $into, $locking, $withRollup
        );
    }

}
