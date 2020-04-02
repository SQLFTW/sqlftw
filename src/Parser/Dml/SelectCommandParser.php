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
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Dml\Select\GroupByExpression;
use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\Sql\Dml\Select\SelectDistinctOption;
use SqlFtw\Sql\Dml\Select\SelectExpression;
use SqlFtw\Sql\Dml\Select\SelectInto;
use SqlFtw\Sql\Dml\Select\SelectLocking;
use SqlFtw\Sql\Dml\Select\SelectLockOption;
use SqlFtw\Sql\Dml\Select\SelectLockWaitOption;
use SqlFtw\Sql\Dml\Select\SelectOption;
use SqlFtw\Sql\Dml\Select\WindowFrame;
use SqlFtw\Sql\Dml\Select\WindowFrameType;
use SqlFtw\Sql\Dml\Select\WindowFrameUnits;
use SqlFtw\Sql\Dml\Select\WindowSpecification;
use SqlFtw\Sql\Dml\WithClause;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Order;
use SqlFtw\Sql\QualifiedName;

class SelectCommandParser
{
    use StrictBehaviorMixin;

    /** @var WithParser */
    private $withParser;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var JoinParser */
    private $joinParser;

    /** @var FileFormatParser */
    private $fileFormatParser;

    public function __construct(
        WithParser $withParser,
        ExpressionParser $expressionParser,
        JoinParser $joinParser,
        FileFormatParser $fileFormatParser
    ) {
        $this->withParser = $withParser;
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
     *     [WINDOW window_name AS (window_spec)
     *       [, window_name AS (window_spec)] ...]
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
    public function parseSelect(TokenList $tokenList, ?WithClause $with = null): SelectCommand
    {
        if ($tokenList->mayConsumeKeyword(Keyword::WITH)) {
            if ($with !== null) {
                throw new ParserException('WITH defined twice.');
            }

            return $this->withParser->parseWith($tokenList->resetPosition(-1));
        }

        $tokenList->consumeKeyword(Keyword::SELECT);

        /** @var SelectDistinctOption $distinct */
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
            $window = $alias = null;
            if ($tokenList->mayConsumeKeyword(Keyword::OVER)) {
                if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
                    $window = $this->parseWindow($tokenList);
                    $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
                } else {
                    $window = $tokenList->consumeName();
                }
            }
            if ($tokenList->mayConsumeKeyword(Keyword::AS)) {
                $alias = $tokenList->consumeName();
            } else {
                $alias = $tokenList->mayConsumeNonKeywordName();
            }
            $what[] = new SelectExpression($value, $alias, $window);
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

        $groupBy = null;
        $withRollup = false;
        if ($tokenList->mayConsumeKeywords(Keyword::GROUP, Keyword::BY)) {
            $groupBy = [];
            do {
                $expression = $this->expressionParser->parseExpression($tokenList);
                /** @var Order $order */
                $order = $tokenList->mayConsumeKeywordEnum(Order::class);
                $groupBy[] = new GroupByExpression($expression, $order);
            } while ($tokenList->mayConsumeComma());

            $withRollup = (bool) $tokenList->mayConsumeKeywords(Keyword::WITH, Keyword::ROLLUP);
        }

        $having = null;
        if ($tokenList->mayConsumeKeyword(Keyword::HAVING)) {
            $having = $this->expressionParser->parseExpression($tokenList);
        }

        $windows = null;
        if ($tokenList->mayConsumeKeyword(Keyword::WINDOW)) {
            $windows = [];
            do {
                $name = $tokenList->consumeName();
                $tokenList->consumeKeyword(Keyword::AS);

                $tokenList->consume(TokenType::LEFT_PARENTHESIS);
                $window = $this->parseWindow($tokenList);
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

                $windows[$name] = $window;
            } while ($tokenList->mayConsumeComma());
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
                $charset = Charset::get($tokenList->consumeName());
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
                    $lockTables[] = new QualifiedName(...$tokenList->consumeQualifiedName());
                } while ($tokenList->mayConsumeComma());
            }
            /** @var SelectLockWaitOption $lockWaitOption */
            $lockWaitOption = $tokenList->mayConsumeKeywordEnum(SelectLockWaitOption::class);
            $locking = new SelectLocking($lockOption, $lockWaitOption, $lockTables);
        }

        return new SelectCommand($what, $from, $where, $groupBy, $having, $with, $windows, $orderBy, $limit, $offset, $distinct, $options, $into, $locking, $withRollup);
    }

    /**
     * window_spec:
     *   [window_name] [partition_clause] [order_clause] [frame_clause]
     *
     * partition_clause:
     *   PARTITION BY expr [, expr] ...
     *
     * order_clause:
     *   ORDER BY expr [ASC|DESC] [, expr [ASC|DESC]] ...
     *
     * frame_clause:
     *   frame_units frame_extent
     *
     * frame_units:
     *   {ROWS | RANGE}
     *
     * frame_extent:
     *   {frame_start | frame_between}
     *
     * frame_between:
     *   BETWEEN frame_start AND frame_end
     *
     * @param TokenList $tokenList
     * @return WindowSpecification
     */
    private function parseWindow(TokenList $tokenList): WindowSpecification
    {
        $reference = $tokenList->mayConsumeName();

        $partitionBy = $orderBy = $frame = null;
        if ($tokenList->mayConsumeKeywords(Keyword::PARTITION, Keyword::BY)) {
            $partitionBy = [];
            do {
                $partitionBy[] = $this->expressionParser->parseExpression($tokenList);
            } while ($tokenList->mayConsumeComma());
        }

        if ($tokenList->mayConsumeKeywords(Keyword::ORDER, Keyword::BY)) {
            $orderBy = $this->expressionParser->parseOrderBy($tokenList);
        }

        $keyword = $tokenList->mayConsumeAnyKeyword(Keyword::ROWS, Keyword::RANGE);
        if ($keyword !== null) {
            $units = WindowFrameUnits::get($keyword);
            $startType = $endType = $startExpression = $endExpression = null;
            if ($tokenList->mayConsumeKeyword(Keyword::BETWEEN)) {
                $this->parseFrameBorder($tokenList, $startType, $startExpression);
                $tokenList->consumeKeyword(Keyword::AND);
                $this->parseFrameBorder($tokenList, $endType, $endExpression);
            } else {
                $this->parseFrameBorder($tokenList, $startType, $startExpression);
            }

            $frame = new WindowFrame($units, $startType, $endType, $startExpression, $endExpression);
        }

        return new WindowSpecification($reference, $partitionBy, $orderBy, $frame);
    }

    /**
     * frame_start, frame_end: {
     *     CURRENT ROW
     *   | UNBOUNDED PRECEDING
     *   | UNBOUNDED FOLLOWING
     *   | expr PRECEDING
     *   | expr FOLLOWING
     * }
     *
     * @param TokenList $tokenList
     * @param WindowFrameType|null $type
     * @param ExpressionNode|null $expression
     */
    private function parseFrameBorder(TokenList $tokenList, ?WindowFrameType &$type, ?ExpressionNode &$expression): void
    {
        if ($tokenList->mayConsumeKeywords(Keyword::CURRENT, Keyword::ROW)) {
            $type = WindowFrameType::get(WindowFrameType::CURRENT_ROW);
        } elseif ($tokenList->mayConsumeKeywords(Keyword::UNBOUNDED, Keyword::PRECEDING)) {
            $type = WindowFrameType::get(WindowFrameType::UNBOUNDED_PRECEDING);
        } elseif ($tokenList->mayConsumeKeywords(Keyword::UNBOUNDED, Keyword::FOLLOWING)) {
            $type = WindowFrameType::get(WindowFrameType::UNBOUNDED_FOLLOWING);
        } else {
            $expression = $this->expressionParser->parseExpression($tokenList);
            $keyword = $tokenList->consumeAnyKeyword(Keyword::PRECEDING, Keyword::FOLLOWING);
            $type = WindowFrameType::get($keyword);
        }
    }

}
