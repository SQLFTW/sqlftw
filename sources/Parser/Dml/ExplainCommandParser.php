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
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\Utility\DescribeTableCommand;
use SqlFtw\Sql\Dml\Utility\ExplainStatementCommand;
use SqlFtw\Sql\Dml\Utility\ExplainType;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use function strtoupper;

class ExplainCommandParser
{
    use StrictBehaviorMixin;

    /** @var QueryParser */
    private $queryParser;

    /** @var InsertCommandParser */
    private $insertCommandParser;

    /** @var UpdateCommandParser */
    private $updateCommandParser;

    /** @var DeleteCommandParser */
    private $deleteCommandParser;

    public function __construct(
        QueryParser $queryParser,
        InsertCommandParser $insertCommandParser,
        UpdateCommandParser $updateCommandParser,
        DeleteCommandParser $deleteCommandParser
    )
    {
        $this->queryParser = $queryParser;
        $this->insertCommandParser = $insertCommandParser;
        $this->updateCommandParser = $updateCommandParser;
        $this->deleteCommandParser = $deleteCommandParser;
    }

    /**
     * {EXPLAIN | DESCRIBE | DESC}
     *     tbl_name [col_name | wild]
     *
     * {EXPLAIN | DESCRIBE | DESC}
     *     [explain_type]
     *     {explainable_stmt | FOR CONNECTION connection_id}
     *
     * explain_type: {
     *     EXTENDED
     *   | PARTITIONS
     *   | FORMAT = format_name
     * }
     *
     * format_name: {
     *     TRADITIONAL
     *   | JSON
     *   | TREE
     * }
     *
     * explainable_stmt: {
     *     SELECT statement
     *   | DELETE statement
     *   | INSERT statement
     *   | REPLACE statement
     *   | UPDATE statement
     * }
     *
     * @return ExplainStatementCommand|DescribeTableCommand
     */
    public function parseExplain(TokenList $tokenList): Command
    {
        $tokenList->expectAnyKeyword(Keyword::EXPLAIN, Keyword::DESCRIBE, Keyword::DESC);

        $type = $tokenList->getAnyKeyword(Keyword::EXTENDED, Keyword::PARTITIONS, Keyword::FORMAT);
        if ($type !== null) {
            if ($type === Keyword::FORMAT) {
                $tokenList->expectOperator(Operator::EQUAL);
                $format = strtoupper($tokenList->expectAnyName(Keyword::TRADITIONAL, Keyword::JSON, 'TREE'));
                $type = ExplainType::get($type . '=' . $format);
            } else {
                $type = ExplainType::get($type);
            }
        }

        $position = $tokenList->getPosition();
        $keyword = $tokenList->getAnyKeyword(Keyword::SELECT, Keyword::WITH, Keyword::TABLE, Keyword::INSERT, Keyword::UPDATE, Keyword::DELETE, Keyword::REPLACE, Keyword::FOR);
        $statement = $connectionId = null;
        switch ($keyword) {
            case Keyword::FOR:
                $tokenList->expectKeyword(Keyword::CONNECTION);
                $connectionId = $tokenList->expectUnsignedInt();
                break;
            case Keyword::SELECT:
            case Keyword::WITH:
                $statement = $this->queryParser->parseQuery($tokenList->resetPosition($position));
                break;
            case Keyword::TABLE:
                $statement = $this->queryParser->parseTable($tokenList->resetPosition($position));
                break;
            case Keyword::INSERT:
                $statement = $this->insertCommandParser->parseInsert($tokenList->resetPosition($position));
                break;
            case Keyword::UPDATE:
                $statement = $this->updateCommandParser->parseUpdate($tokenList->resetPosition($position));
                break;
            case Keyword::DELETE:
                $statement = $this->deleteCommandParser->parseDelete($tokenList->resetPosition($position));
                break;
            case Keyword::REPLACE:
                $statement = $this->insertCommandParser->parseReplace($tokenList->resetPosition($position));
                break;
            case null:
                // DESCRIBE
                $table = $tokenList->expectQualifiedName();
                $column = $tokenList->getName();
                if ($column === null) {
                    $column = $tokenList->getString();
                }

                return new DescribeTableCommand($table, $column);
        }

        return new ExplainStatementCommand($statement, $connectionId, $type);
    }

}
