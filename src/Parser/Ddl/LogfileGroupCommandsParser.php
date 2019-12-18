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
use SqlFtw\Sql\Ddl\LogfileGroup\AlterLogfileGroupCommand;
use SqlFtw\Sql\Ddl\LogfileGroup\CreateLogfileGroupCommand;
use SqlFtw\Sql\Ddl\LogfileGroup\DropLogfileGroupCommand;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;

/**
 * MySQL 5.7 only
 */
class LogfileGroupCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * ALTER LOGFILE GROUP logfile_group
     *     ADD UNDOFILE 'file_name'
     *     [INITIAL_SIZE [=] size]
     *     [WAIT]
     *     ENGINE [=] engine_name
     */
    public function parseAlterLogfileGroup(TokenList $tokenList): AlterLogfileGroupCommand
    {
        $tokenList->consumeKeywords(Keyword::ALTER, Keyword::LOGFILE, Keyword::GROUP);
        $name = $tokenList->consumeName();
        $tokenList->consumeKeywords(Keyword::ADD, Keyword::UNDOFILE);
        $undoFile = $tokenList->consumeString();

        $initialSize = null;
        if ($tokenList->mayConsumeKeyword(Keyword::INITIAL_SIZE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $initialSize = $tokenList->consumeInt();
        }
        $wait = (bool) $tokenList->mayConsumeKeyword(Keyword::WAIT);

        $tokenList->consumeKeyword(Keyword::ENGINE);
        $tokenList->mayConsumeOperator(Operator::EQUAL);
        $engine = $tokenList->consumeNameOrString();
        $tokenList->expectEnd();

        return new AlterLogfileGroupCommand($name, $engine, $undoFile, $initialSize, $wait);
    }

    /**
     * CREATE LOGFILE GROUP logfile_group
     *     ADD UNDOFILE 'undo_file'
     *     [INITIAL_SIZE [=] initial_size]
     *     [UNDO_BUFFER_SIZE [=] undo_buffer_size]
     *     [REDO_BUFFER_SIZE [=] redo_buffer_size]
     *     [NODEGROUP [=] nodegroup_id]
     *     [WAIT]
     *     [COMMENT [=] comment_text]
     *     ENGINE [=] engine_name
     */
    public function parseCreateLogfileGroup(TokenList $tokenList): CreateLogfileGroupCommand
    {
        $tokenList->consumeKeywords(Keyword::CREATE, Keyword::LOGFILE, Keyword::GROUP);
        $name = $tokenList->consumeName();
        $tokenList->consumeKeywords(Keyword::ADD, Keyword::UNDOFILE);
        $undoFile = $tokenList->consumeString();

        $initialSize = $undoBufferSize = $redoBufferSize = $nodeGroup = $comment = null;
        if ($tokenList->mayConsumeKeyword(Keyword::INITIAL_SIZE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $initialSize = $tokenList->consumeInt();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::UNDO_BUFFER_SIZE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $undoBufferSize = $tokenList->consumeInt();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::REDO_BUFFER_SIZE)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $redoBufferSize = $tokenList->consumeInt();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::NODEGROUP)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $nodeGroup = $tokenList->consumeInt();
        }
        $wait = (bool) $tokenList->mayConsumeKeyword(Keyword::WAIT);
        if ($tokenList->mayConsumeKeyword(Keyword::COMMENT)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $comment = $tokenList->consumeString();
        }

        $tokenList->consumeKeyword(Keyword::ENGINE);
        $tokenList->mayConsumeOperator(Operator::EQUAL);
        $engine = $tokenList->consumeNameOrString();
        $tokenList->expectEnd();

        return new CreateLogfileGroupCommand($name, $engine, $undoFile, $initialSize, $undoBufferSize, $redoBufferSize, $nodeGroup, $wait, $comment);
    }

    /**
     * DROP LOGFILE GROUP logfile_group
     *     ENGINE [=] engine_name
     */
    public function parseDropLogfileGroup(TokenList $tokenList): DropLogfileGroupCommand
    {
        $tokenList->consumeKeywords(Keyword::DROP, Keyword::LOGFILE, Keyword::GROUP);
        $name = $tokenList->consumeName();
        $tokenList->consumeKeyword(Keyword::ENGINE);
        $tokenList->mayConsumeOperator(Operator::EQUAL);
        $engine = $tokenList->consumeNameOrString();
        $tokenList->expectEnd();

        return new DropLogfileGroupCommand($name, $engine);
    }

}
