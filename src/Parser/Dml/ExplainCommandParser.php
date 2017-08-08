<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\Utility\DescribeTableCommand;
use SqlFtw\Sql\Dml\Utility\ExplainStatementCommand;
use SqlFtw\Sql\Dml\Utility\ExplainType;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\TableName;

class ExplainCommandParser
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\Dml\SelectCommandParser */
    private $selectCommandParser;

    /** @var \SqlFtw\Parser\Dml\InsertCommandParser */
    private $insertCommandParser;

    /** @var \SqlFtw\Parser\Dml\UpdateCommandParser */
    private $updateCommandParser;

    /** @var \SqlFtw\Parser\Dml\DeleteCommandParser */
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
        $tokenList->consumeAnyKeyword(Keyword::EXPLAIN, Keyword::DESCRIBE, Keyword::DESC);

        $tableName = $tokenList->mayConsumeQualifiedName();
        if ($tableName !== null) {
            $table = new TableName(...$tableName);
            $column = $tokenList->mayConsumeName();
            if ($column === null) {
                $column = $tokenList->mayConsumeString();
            }

            return new DescribeTableCommand($table, $column);
        }

        $type = $tokenList->consumeAnyKeyword(Keyword::EXTENDED, Keyword::PARTITIONS, Keyword::FORMAT);
        if ($type !== null) {
            if ($type === Keyword::FORMAT) {
                $tokenList->consumeOperator(Operator::EQUAL);
                $format = $tokenList->consumeAnyKeyword(Keyword::JSON, Keyword::TRADITIONAL);
                $type = ExplainType::get($type . ' = ' . $format);
            } else {
                $type = ExplainType::get($type);
            }
        }

        $statement = $connectionId = null;
        switch ($tokenList->consumeAnyKeyword(Keyword::SELECT, Keyword::INSERT, Keyword::UPDATE, Keyword::DELETE, Keyword::REPLACE, Keyword::FOR)) {
            case Keyword::FOR:
                $tokenList->consumeKeyword(Keyword::CONNECTION);
                /** @var int $connectionId */
                $connectionId = $tokenList->consumeInt();
                break;
            case Keyword::SELECT:
                $statement = $this->selectCommandParser->parseSelect($tokenList);
                break;
            case Keyword::INSERT:
                $statement = $this->insertCommandParser->parseInsert($tokenList);
                break;
            case Keyword::UPDATE:
                $statement = $this->updateCommandParser->parseUpdate($tokenList);
                break;
            case Keyword::DELETE:
                $statement = $this->deleteCommandParser->parseDelete($tokenList);
                break;
            case Keyword::REPLACE:
                $statement = $this->insertCommandParser->parseReplace($tokenList);
                break;
        }

        return new ExplainStatementCommand($statement, $connectionId, $type);
    }

}
