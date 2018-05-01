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
use SqlFtw\Sql\Dml\Handler\HandlerCloseCommand;
use SqlFtw\Sql\Dml\Handler\HandlerOpenCommand;
use SqlFtw\Sql\Dml\Handler\HandlerReadCommand;
use SqlFtw\Sql\Dml\Handler\HandlerReadWhat;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class HandlerCommandsParser
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\ExpressionParser */
    private $expressionParser;

    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    /**
     * HANDLER tbl_name OPEN [[AS] alias]
     */
    public function parseHandlerOpen(TokenList $tokenList): HandlerOpenCommand
    {
        $tokenList->consumeKeyword(Keyword::HANDLER);
        $table = new QualifiedName(...$tokenList->consumeQualifiedName());
        $tokenList->consumeKeyword(Keyword::OPEN);

        $tokenList->mayConsumeKeyword(Keyword::AS);
        $alias = $tokenList->mayConsumeName();

        return new HandlerOpenCommand($table, $alias);
    }

    /**
     * HANDLER tbl_name READ index_name { = | <= | >= | < | > } (value1,value2,...)
     *     [ WHERE where_condition ] [LIMIT ... ]
     * HANDLER tbl_name READ index_name { FIRST | NEXT | PREV | LAST }
     *     [ WHERE where_condition ] [LIMIT ... ]
     * HANDLER tbl_name READ { FIRST | NEXT }
     *     [ WHERE where_condition ] [LIMIT ... ]
     */
    public function parseHandlerRead(TokenList $tokenList): HandlerReadCommand
    {
        $tokenList->consumeKeyword(Keyword::HANDLER);
        $table = new QualifiedName(...$tokenList->consumeQualifiedName());
        $tokenList->consumeKeyword(Keyword::READ);

        $values = null;
        $index = $tokenList->mayConsumeName();
        if ($index !== null) {
            $what = $tokenList->mayConsumeAnyKeyword(...HandlerReadWhat::getKeywords());
            if ($what === null) {
                $what = $tokenList->consumeAnyOperator(...HandlerReadWhat::getOperators());
                $values = [];
                $tokenList->consume(TokenType::LEFT_PARENTHESIS);
                do {
                    $values[] = $this->expressionParser->parseLiteralValue($tokenList);
                } while ($tokenList->mayConsumeComma());
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            }
        } else {
            /** @var \SqlFtw\Sql\Dml\Handler\HandlerReadWhat $what */
            $what = $tokenList->consumeKeywordEnum(HandlerReadWhat::class);
        }

        $where = $limit = $offset = null;
        if ($tokenList->mayConsumeKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }
        if ($tokenList->mayConsumeKeyword(Keyword::LIMIT)) {
            [$limit, $offset] = $this->expressionParser->parseLimitAndOffset($tokenList);
        }

        return new HandlerReadCommand($table, $what, $index, $values, $where, $limit, $offset);
    }

    /**
     * HANDLER tbl_name CLOSE
     */
    public function parseHandlerClose(TokenList $tokenList): HandlerCloseCommand
    {
        $tokenList->consumeKeyword(Keyword::HANDLER);
        $table = new QualifiedName(...$tokenList->consumeQualifiedName());
        $tokenList->consumeKeyword(Keyword::CLOSE);

        return new HandlerCloseCommand($table);
    }

}
