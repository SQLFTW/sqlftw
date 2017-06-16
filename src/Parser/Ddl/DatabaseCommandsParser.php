<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Ddl\Database\AlterDatabaseCommand;
use SqlFtw\Sql\Ddl\Database\CreateDatabaseCommand;
use SqlFtw\Sql\Ddl\Database\DropDatabaseCommand;
use SqlFtw\Sql\Keyword;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Operator;

class DatabaseCommandsParser
{
    use \Dogma\StrictBehaviorMixin;

    /**
     * ALTER {DATABASE | SCHEMA} [db_name]
     *     alter_specification ...
     *
     * alter_specification:
     *     [DEFAULT] CHARACTER SET [=] charset_name
     *   | [DEFAULT] COLLATE [=] collation_name
     */
    public function parseAlterDatabase(TokenList $tokenList): AlterDatabaseCommand
    {
        $tokenList->consumeKeyword(Keyword::ALTER);
        $tokenList->consumeAnyKeyword(Keyword::DATABASE, Keyword::SCHEMA);
        $database = $tokenList->mayConsumeName();

        [$charset, $collation] = $this->parseDefaults($tokenList);

        return new AlterDatabaseCommand($database, $charset, $collation);
    }

    /**
     * CREATE {DATABASE | SCHEMA} [IF NOT EXISTS] db_name
     *     [create_specification] ...
     *
     * create_specification:
     *     [DEFAULT] CHARACTER SET [=] charset_name
     *   | [DEFAULT] COLLATE [=] collation_name
     */
    public function parseCreateDatabase(TokenList $tokenList): CreateDatabaseCommand
    {
        $tokenList->consumeKeyword(Keyword::CREATE);
        $tokenList->consumeAnyKeyword(Keyword::DATABASE, Keyword::SCHEMA);
        $ifNotExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);
        $database = $tokenList->consumeName();

        [$charset, $collation] = $this->parseDefaults($tokenList);

        return new CreateDatabaseCommand($database, $charset, $collation, $ifNotExists);
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return string[]|\SqlFtw\Sql\Charset[]|null[]
     */
    private function parseDefaults(TokenList $tokenList): array
    {
        $charset = $collation = null;
        $tokenList->mayConsumeKeyword(Keyword::DEFAULT);
        $token = $tokenList->consumeAnyKeyword(Keyword::CHARACTER, Keyword::COLLATION);
        if ($token === Keyword::CHARACTER) {
            $tokenList->consumeKeyword(Keyword::SET);
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $charset = Charset::get($tokenList->consumeString());
        } else {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $collation = $tokenList->consumeString();
        }

        $token = $tokenList->mayConsumeAnyKeyword(Keyword::CHARACTER, Keyword::COLLATION);
        if ($token) {
            if ($token === Keyword::CHARACTER) {
                $tokenList->consumeKeyword(Keyword::SET);
                $tokenList->mayConsumeOperator(Operator::EQUAL);
                $charset = Charset::get($tokenList->consumeString());
            } else {
                $tokenList->mayConsumeOperator(Operator::EQUAL);
                $collation = $tokenList->consumeString();
            }
        }

        return [$charset, $collation];
    }

    /**
     * DROP {DATABASE | SCHEMA} [IF EXISTS] db_name
     */
    public function parseDropDatabase(TokenList $tokenList): DropDatabaseCommand
    {
        $tokenList->consumeKeyword(Keyword::DROP);
        $tokenList->consumeAnyKeyword(Keyword::DATABASE, Keyword::SCHEMA);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);
        $database = $tokenList->consumeName();

        return new DropDatabaseCommand($database, $ifExists);
    }

}
