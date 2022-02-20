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
use SqlFtw\Sql\QualifiedName;

class ExplainCommandParser
{
    use StrictBehaviorMixin;

    /** @var SelectCommandParser */
    private $selectCommandParser;

    /** @var InsertCommandParser */
    private $insertCommandParser;

    /** @var UpdateCommandParser */
    private $updateCommandParser;

    /** @var DeleteCommandParser */
    private $deleteCommandParser;

    public function __construct(
        SelectCommandParser $selectCommandParser,
        InsertCommandParser $insertCommandParser,
        UpdateCommandParser $updateCommandParser,
        DeleteCommandParser $deleteCommandParser
    )
    {
        $this->selectCommandParser = $selectCommandParser;
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
     * }
     *
     * explainable_stmt: {
     *     SELECT statement
     *   | DELETE statement
     *   | INSERT statement
     *   | REPLACE statement
     *   | UPDATE statement
     * }
     */
    public function parseExplain(TokenList $tokenList): Command
    {
        $tokenList->expectAnyKeyword(Keyword::EXPLAIN, Keyword::DESCRIBE, Keyword::DESC);

        $type = $tokenList->getAnyKeyword(Keyword::EXTENDED, Keyword::PARTITIONS, Keyword::FORMAT);
        if ($type !== null) {
            if ($type === Keyword::FORMAT) {
                $tokenList->expectOperator(Operator::EQUAL);
                $format = $tokenList->expectAnyKeyword(Keyword::JSON, Keyword::TRADITIONAL);
                $type = ExplainType::get($type . '=' . $format);
            } else {
                $type = ExplainType::get($type);
            }
        }

        $position = $tokenList->getPosition();
        $keyword = $tokenList->getAnyKeyword(Keyword::SELECT, Keyword::INSERT, Keyword::UPDATE, Keyword::DELETE, Keyword::REPLACE, Keyword::FOR);
        $statement = $connectionId = null;
        switch ($keyword) {
            case Keyword::FOR:
                $tokenList->expectKeyword(Keyword::CONNECTION);
                $connectionId = $tokenList->expectInt();
                break;
            case Keyword::SELECT:
                $statement = $this->selectCommandParser->parseSelect($tokenList->resetPosition($position));
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
                $qualifiedName = $tokenList->expectQualifiedName();
                $table = new QualifiedName(...$qualifiedName);
                $column = $tokenList->getName();
                if ($column === null) {
                    $column = $tokenList->getString();
                }

                return new DescribeTableCommand($table, $column);
        }
        $tokenList->expectEnd();

        return new ExplainStatementCommand($statement, $connectionId, $type);
    }

}
