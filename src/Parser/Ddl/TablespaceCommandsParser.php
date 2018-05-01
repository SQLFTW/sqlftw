<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Ddl\Tablespace\AlterTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\CreateTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\DropTablespaceCommand;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;

class TablespaceCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * ALTER TABLESPACE tablespace_name
     *     {ADD|DROP} DATAFILE 'file_name'
     *     [INITIAL_SIZE [=] size]
     *     [WAIT]
     *     ENGINE [=] engine_name
     */
    public function parseAlterTablespace(TokenList $tokenList): AlterTablespaceCommand
    {
        $tokenList->consumeKeywords(Keyword::ALTER, Keyword::TABLESPACE);
        $name = $tokenList->consumeName();

        $drop = $tokenList->consumeAnyKeyword(Keyword::ADD, Keyword::DROP) === Keyword::DROP;
        $tokenList->consumeKeyword(Keyword::DATAFILE);
        $file = $tokenList->consumeString();
        $initialSize = null;
        if ($tokenList->mayConsumeKeyword(Keyword::INITIAL_SIZE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            /** @var int $initialSize */
            $initialSize = $tokenList->consumeInt();
        }

        $wait = (bool) $tokenList->mayConsumeKeyword(Keyword::WAIT);

        $engine = null;
        if ($tokenList->mayConsumeKeyword(Keyword::ENGINE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $engine = $tokenList->consumeName();
        }

        return new AlterTablespaceCommand($name, $file, $drop, $wait, $initialSize, $engine);
    }

    /**
     * CREATE TABLESPACE tablespace_name
     *     ADD DATAFILE 'file_name'
     *     [FILE_BLOCK_SIZE = value]
     *         [ENGINE [=] engine_name]
     */
    public function parseCreateTablespace(TokenList $tokenList): CreateTablespaceCommand
    {
        $tokenList->consumeKeywords(Keyword::CREATE, Keyword::TABLESPACE);
        $name = $tokenList->consumeName();

        $tokenList->consumeKeywords(Keyword::ADD, Keyword::DATAFILE);
        $file = $tokenList->consumeString();
        $fileBlockSize = null;
        if ($tokenList->mayConsumeKeyword(Keyword::FILE_BLOCK_SIZE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            /** @var int $fileBlockSize */
            $fileBlockSize = $tokenList->consumeInt();
        }

        $engine = null;
        if ($tokenList->mayConsumeKeyword(Keyword::ENGINE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $engine = $tokenList->consumeName();
        }

        return new CreateTablespaceCommand($name, $file, $fileBlockSize, $engine);
    }

    /**
     * DROP TABLESPACE tablespace_name
     *     [ENGINE [=] engine_name]
     */
    public function parseDropTablespace(TokenList $tokenList): DropTablespaceCommand
    {
        $tokenList->consumeKeywords(Keyword::CREATE, Keyword::TABLESPACE);
        $name = $tokenList->consumeName();
        $engine = null;
        if ($tokenList->mayConsumeKeyword(Keyword::ENGINE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $engine = $tokenList->consumeName();
        }

        return new DropTablespaceCommand($name, $engine);
    }

}
