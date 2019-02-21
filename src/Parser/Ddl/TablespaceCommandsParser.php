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
use SqlFtw\Sql\Ddl\Tablespace\TablespaceOption;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;

class TablespaceCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * ALTER [UNDO] TABLESPACE tablespace_name
     *     [{ADD|DROP} DATAFILE 'file_name'] -- NDB only
     *     [INITIAL_SIZE [=] size]      -- NDB only
     *     [WAIT]                       -- NDB only
     *     [RENAME TO tablespace_name]
     *     [SET {ACTIVE|INACTIVE}]      -- InnoDB only
     *     [ENCRYPTION [=] {'Y' | 'N'}] -- InnoDB only
     *     [ENGINE [=] engine_name]
     */
    public function parseAlterTablespace(TokenList $tokenList): AlterTablespaceCommand
    {
        $tokenList->consumeKeyword(Keyword::ALTER);
        $undo = (bool) $tokenList->mayConsumeKeyword(Keyword::UNDO);
        $tokenList->consumeKeyword(Keyword::TABLESPACE);

        $name = $tokenList->consumeName();

        $options = [];
        $keyword = $tokenList->mayConsumeAnyKeyword(Keyword::ADD, Keyword::DROP);
        if ($keyword !== null) {
            $tokenList->consumeKeyword(Keyword::DATAFILE);
            $options[$keyword . ' ' . Keyword::DATAFILE] = $tokenList->consumeString();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::INITIAL_SIZE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[TablespaceOption::INITIAL_SIZE] = $tokenList->consumeInt();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::WAIT) !== null) {
            $options[TablespaceOption::WAIT] = true;
        }
        if ($tokenList->mayConsumeKeywords(Keyword::RENAME, Keyword::TO)) {
            $options[TablespaceOption::RENAME_TO] = $tokenList->consumeName();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::SET)) {
            $options[TablespaceOption::SET] = $tokenList->consumeAnyKeyword(Keyword::ACTIVE, Keyword::INACTIVE);
        }
        if ($tokenList->mayConsumeKeywords(Keyword::ENCRYPTION)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[TablespaceOption::ENCRYPTION] = $tokenList->consumeBool();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::ENGINE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[TablespaceOption::ENGINE] = $tokenList->consumeName();
        }

        return new AlterTablespaceCommand($name, $options, $undo);
    }

    /**
     * CREATE [UNDO] TABLESPACE tablespace_name
     *     [ADD DATAFILE 'file_name']
     *     [FILE_BLOCK_SIZE = value]        -- InnoDB only
     *     [ENCRYPTION [=] {'Y' | 'N'}]     -- InnoDB only
     *     USE LOGFILE GROUP logfile_group  -- NDB only
     *     [EXTENT_SIZE [=] extent_size]    -- NDB only
     *     [INITIAL_SIZE [=] initial_size]  -- NDB only
     *     [AUTOEXTEND_SIZE [=] autoextend_size] -- NDB only
     *     [MAX_SIZE [=] max_size]          -- NDB only
     *     [NODEGROUP [=] nodegroup_id]     -- NDB only
     *     [WAIT]                           -- NDB only
     *     [COMMENT [=] 'string']           -- NDB only
     *     [ENGINE [=] engine_name]
     */
    public function parseCreateTablespace(TokenList $tokenList): CreateTablespaceCommand
    {
        $tokenList->consumeKeyword(Keyword::CREATE);
        $undo = (bool) $tokenList->mayConsumeKeyword(Keyword::UNDO);
        $tokenList->consumeKeyword(Keyword::TABLESPACE);

        $name = $tokenList->consumeName();

        $options = [];
        if ($tokenList->mayConsumeKeywords(Keyword::ADD, Keyword::DATAFILE)) {
            $options[TablespaceOption::ADD_DATAFILE] = $tokenList->consumeString();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::FILE_BLOCK_SIZE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[TablespaceOption::FILE_BLOCK_SIZE] = $tokenList->consumeInt();
        }
        if ($tokenList->mayConsumeKeywords(Keyword::ENCRYPTION)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[TablespaceOption::ENCRYPTION] = $tokenList->consumeBool();
        }
        if ($tokenList->mayConsumeKeywords(Keyword::USE, Keyword::LOGFILE, Keyword::GROUP)) {
            $options[TablespaceOption::USE_LOGFILE_GROUP] = $tokenList->consumeName();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::EXTENT_SIZE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[TablespaceOption::EXTENT_SIZE] = $tokenList->consumeInt();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::INITIAL_SIZE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[TablespaceOption::INITIAL_SIZE] = $tokenList->consumeInt();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::AUTOEXTEND_SIZE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[TablespaceOption::AUTOEXTEND_SIZE] = $tokenList->consumeInt();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::MAX_SIZE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[TablespaceOption::MAX_SIZE] = $tokenList->consumeInt();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::NODEGROUP)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[TablespaceOption::NODEGROUP] = $tokenList->consumeInt();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::WAIT) !== null) {
            $options[TablespaceOption::WAIT] = true;
        }
        if ($tokenList->mayConsumeKeyword(Keyword::COMMENT)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[TablespaceOption::COMMENT] = $tokenList->consumeString();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::ENGINE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $options[TablespaceOption::ENGINE] = $tokenList->consumeName();
        }

        return new CreateTablespaceCommand($name, $options, $undo);
    }

    /**
     * DROP [UNDO] TABLESPACE tablespace_name
     *     [ENGINE [=] engine_name]
     */
    public function parseDropTablespace(TokenList $tokenList): DropTablespaceCommand
    {
        $tokenList->consumeKeyword(Keyword::DROP);
        $undo = (bool) $tokenList->mayConsumeKeyword(Keyword::UNDO);
        $tokenList->consumeKeyword(Keyword::TABLESPACE);

        $name = $tokenList->consumeName();
        $engine = null;
        if ($tokenList->mayConsumeKeyword(Keyword::ENGINE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $engine = $tokenList->consumeName();
        }

        return new DropTablespaceCommand($name, $engine, $undo);
    }

}
