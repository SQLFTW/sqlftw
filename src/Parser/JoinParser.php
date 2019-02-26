<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Sql\Dml\TableReference\EscapedTableReference;
use SqlFtw\Sql\Dml\TableReference\IndexHint;
use SqlFtw\Sql\Dml\TableReference\IndexHintAction;
use SqlFtw\Sql\Dml\TableReference\IndexHintTarget;
use SqlFtw\Sql\Dml\TableReference\InnerJoin;
use SqlFtw\Sql\Dml\TableReference\JoinSide;
use SqlFtw\Sql\Dml\TableReference\NaturalJoin;
use SqlFtw\Sql\Dml\TableReference\OuterJoin;
use SqlFtw\Sql\Dml\TableReference\StraightJoin;
use SqlFtw\Sql\Dml\TableReference\TableReferenceList;
use SqlFtw\Sql\Dml\TableReference\TableReferenceNode;
use SqlFtw\Sql\Dml\TableReference\TableReferenceParentheses;
use SqlFtw\Sql\Dml\TableReference\TableReferenceSubquery;
use SqlFtw\Sql\Dml\TableReference\TableReferenceTable;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;
use function count;

class JoinParser
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\ParserFactory */
    private $parserFactory;

    /** @var \SqlFtw\Parser\ExpressionParser */
    private $expressionParser;

    public function __construct(
        ParserFactory $parserFactory,
        ExpressionParser $expressionParser
    )
    {
        $this->parserFactory = $parserFactory;
        $this->expressionParser = $expressionParser;
    }

    /**
     * table_references:
     *     escaped_table_reference [, escaped_table_reference] ...
     *
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Dml\TableReference\TableReferenceNode
     */
    public function parseTableReferences(TokenList $tokenList): TableReferenceNode
    {
        $references = [];
        do {
            $references[] = $this->parseTableReference($tokenList);
        } while ($tokenList->mayConsumeComma());

        if (count($references) === 1) {
            return $references[0];
        } else {
            return new TableReferenceList($references);
        }
    }

    /**
     * escaped_table_reference:
     *     table_reference
     *   | { OJ table_reference }
     */
    public function parseTableReference(TokenList $tokenList): TableReferenceNode
    {
        if ($tokenList->mayConsume(TokenType::LEFT_CURLY_BRACKET)) {
            $token = $tokenList->consumeName();
            if ($token !== 'OJ') {
                $tokenList->expected('Expected ODBC escaped table reference introducer "OJ".');
                exit;
            } else {
                $reference = $this->parseTableReference($tokenList);
                $tokenList->consume(TokenType::RIGHT_CURLY_BRACKET);
                return new EscapedTableReference($reference);
            }
        } else {
            return $this->parseTableReferenceInternal($tokenList);
        }
    }

    /**
     * table_reference:
     *     table_factor
     *   | join_table
     *
     * join_table:
     *     table_reference [INNER | CROSS] JOIN table_factor [join_condition]
     *   | table_reference STRAIGHT_JOIN table_factor
     *   | table_reference STRAIGHT_JOIN table_factor ON conditional_expr
     *   | table_reference {LEFT|RIGHT} [OUTER] JOIN table_reference join_condition
     *   | table_reference NATURAL [INNER | {LEFT|RIGHT} [OUTER]] JOIN table_factor
     *
     * join_condition:
     *     ON conditional_expr
     *   | USING (column_list)
     */
    private function parseTableReferenceInternal(TokenList $tokenList): TableReferenceNode
    {
        $left = $this->parseTableFactor($tokenList);

        do {
            if ($tokenList->mayConsumeKeyword(Keyword::STRAIGHT_JOIN)) {
                // STRAIGHT_JOIN
                $right = $this->parseTableFactor($tokenList);
                $condition = null;
                if ($tokenList->mayConsumeKeyword(Keyword::ON)) {
                    $condition = $this->expressionParser->parseExpression($tokenList);
                }

                $left = new StraightJoin($left, $right, $condition);
                continue;
            }
            if ($tokenList->mayConsumeKeyword(Keyword::NATURAL)) {
                // NATURAL JOIN
                $side = null;
                if ($tokenList->mayConsumeKeyword(Keyword::INNER) === null) {
                    /** @var \SqlFtw\Sql\Dml\TableReference\JoinSide $side */
                    $side = $tokenList->mayConsumeKeywordEnum(JoinSide::class);
                    if ($side !== null) {
                        $tokenList->mayConsumeKeyword(Keyword::OUTER);
                    }
                }
                $tokenList->consumeKeyword(Keyword::JOIN);
                $right = $this->parseTableFactor($tokenList);

                $left = new NaturalJoin($left, $right, $side);
                continue;
            }
            /** @var \SqlFtw\Sql\Dml\TableReference\JoinSide $side */
            $side = $tokenList->mayConsumeKeywordEnum(JoinSide::class);
            if ($side !== null) {
                // OUTER JOIN
                $tokenList->mayConsumeKeyword(Keyword::OUTER);
                $right = $this->parseTableReferenceInternal($tokenList);
                [$on, $using] = $this->parseJoinCondition($tokenList);

                $left = new OuterJoin($left, $right, $side, $on, $using);
                continue;
            }
            $keyword = $tokenList->mayConsumeAnyKeyword(Keyword::INNER, Keyword::CROSS, Keyword::JOIN);
            if ($keyword !== null) {
                // INNER JOIN
                $cross = false;
                if ($keyword === Keyword::INNER) {
                    $tokenList->consumeKeyword(Keyword::JOIN);
                } elseif ($keyword === Keyword::CROSS) {
                    $tokenList->consumeKeyword(Keyword::JOIN);
                    $cross = true;
                }
                $right = $this->parseTableFactor($tokenList);
                [$on, $using] = $this->parseJoinCondition($tokenList);

                $left = new InnerJoin($left, $right, $cross, $on, $using);
                continue;
            }

            return $left;
        } while (true);
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Expression\ExpressionNode[]|string[][]
     */
    private function parseJoinCondition(TokenList $tokenList): array
    {
        $on = $using = null;
        if ($tokenList->mayConsumeKeyword(Keyword::ON)) {
            $on = $this->expressionParser->parseExpression($tokenList);
        } elseif ($tokenList->mayConsumeKeyword(Keyword::USING)) {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $using = [];
            do {
                $using[] = $tokenList->consumeName();
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }

        return [$on, $using];
    }

    /**
     * table_factor:
     *     tbl_name [PARTITION (partition_names)] [[AS] alias] [index_hint_list]
     *   | [LATERAL] [(] table_subquery [)] [AS] alias [(col_list)]
     *   | ( table_references )
     */
    private function parseTableFactor(TokenList $tokenList): TableReferenceNode
    {
        $selectInParentheses = null;
        if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
            $selectInParentheses = (bool) $tokenList->mayConsumeKeyword(Keyword::SELECT);
            if ($selectInParentheses) {
                $references = $this->parseTableReferences($tokenList);
                $tokenList->mayConsume(TokenType::RIGHT_PARENTHESIS);

                return new TableReferenceParentheses($references);
            }
        }

        $keyword = $tokenList->mayConsumeAnyKeyword(Keyword::SELECT, Keyword::LATERAL);
        if ($selectInParentheses || $keyword !== null) {
            if ($keyword === Keyword::LATERAL) {
                if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
                    $selectInParentheses = true;
                }
                $tokenList->consumeKeyword(Keyword::SELECT);
            }

            $query = $this->parserFactory->getSelectCommandParser()->parseSelect($tokenList->resetPosition(-1));

            if ($selectInParentheses) {
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            }

            $tokenList->mayConsumeKeyword(Keyword::AS);
            $alias = $tokenList->consumeName();
            $columns = null;
            if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
                $columns = [];
                do {
                    $columns[] = $tokenList->consumeName();
                } while ($tokenList->mayConsumeComma());
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            }

            return new TableReferenceSubquery($query, $alias, $columns, $selectInParentheses, $keyword === Keyword::LATERAL);
        } else {
            $table = new QualifiedName(...$tokenList->consumeQualifiedName());
            $partitions = null;
            if ($tokenList->mayConsumeKeyword(Keyword::PARTITION)) {
                $tokenList->consume(TokenType::LEFT_PARENTHESIS);
                $partitions = [];
                do {
                    $partitions[] = $tokenList->consumeName();
                } while ($tokenList->mayConsumeComma());
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            }
            if ($tokenList->mayConsumeKeyword(Keyword::AS)) {
                $alias = $tokenList->consumeName();
            } else {
                $alias = $tokenList->mayConsumeName();
            }
            $indexHints = null;
            if ($tokenList->mayConsumeAnyKeyword(Keyword::USE, Keyword::IGNORE, Keyword::FORCE)) {
                $indexHints = $this->parseIndexHints($tokenList->resetPosition(-1));
            }

            return new TableReferenceTable($table, $alias, $partitions, $indexHints);
        }
    }

    /**
     * index_hint_list:
     *     index_hint [, index_hint] ...
     *
     * index_hint:
     *     USE {INDEX|KEY} [FOR {JOIN|ORDER BY|GROUP BY}] ([index_list])
     *   | IGNORE {INDEX|KEY} [FOR {JOIN|ORDER BY|GROUP BY}] (index_list)
     *   | FORCE {INDEX|KEY} [FOR {JOIN|ORDER BY|GROUP BY}] (index_list)
     *
     * index_list:
     *     index_name [, index_name] ...
     *
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Dml\TableReference\IndexHint[]
     */
    private function parseIndexHints(TokenList $tokenList): array
    {
        $hints = [];
        do {
            /** @var \SqlFtw\Sql\Dml\TableReference\IndexHintAction $action */
            $action = $tokenList->mayConsumeKeywordEnum(IndexHintAction::class);
            $tokenList->mayConsumeAnyKeyword(Keyword::INDEX, Keyword::KEY);
            $target = null;
            if ($tokenList->mayConsumeKeyword(Keyword::FOR)) {
                $keyword = $tokenList->consumeAnyKeyword(Keyword::JOIN, Keyword::ORDER, Keyword::GROUP);
                if ($keyword === Keyword::JOIN) {
                    $target = IndexHintTarget::get(IndexHintTarget::JOIN);
                } else {
                    $tokenList->consumeKeyword(Keyword::BY);
                    $target = IndexHintTarget::get($keyword . ' ' . Keyword::BY);
                }
            }

            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $indexes = [];
            do {
                $indexes[] = $tokenList->consumeName();
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);

            $hints[] = new IndexHint($action, $target, $indexes);
        } while ($tokenList->mayConsumeComma());

        return $hints;
    }

}
