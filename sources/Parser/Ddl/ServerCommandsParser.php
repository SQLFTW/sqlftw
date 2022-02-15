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
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Ddl\Server\AlterServerCommand;
use SqlFtw\Sql\Ddl\Server\CreateServerCommand;
use SqlFtw\Sql\Ddl\Server\DropServerCommand;
use SqlFtw\Sql\Keyword;

class ServerCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * ALTER SERVER server_name
     *     OPTIONS (option [, option] ...)
     *
     * option:
     *     HOST character-literal
     *   | DATABASE character-literal
     *   | USER character-literal
     *   | PASSWORD character-literal
     *   | SOCKET character-literal
     *   | OWNER character-literal
     *   | PORT numeric-literal
     */
    public function parseAlterServer(TokenList $tokenList): AlterServerCommand
    {
        $tokenList->consumeKeywords(Keyword::ALTER, Keyword::SERVER);
        $name = $tokenList->consumeName();

        $tokenList->consumeKeyword(Keyword::OPTIONS);
        $tokenList->consume(TokenType::LEFT_PARENTHESIS);
        $host = $schema = $user = $password = $socket = $owner = $port = null;
        do {
            if ($tokenList->mayConsumeKeyword(Keyword::HOST)) {
                $host = $tokenList->consumeString();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::DATABASE)) {
                $schema = $tokenList->consumeString();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::USER)) {
                $user = $tokenList->consumeString();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::PASSWORD)) {
                $password = $tokenList->consumeString();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::SOCKET)) {
                $socket = $tokenList->consumeString();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::OWNER)) {
                $owner = $tokenList->consumeString();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::PORT)) {
                $port = $tokenList->consumeInt();
            }
        } while ($tokenList->mayConsumeComma());
        if ($host === null && $schema === null && $user === null && $password === null && $socket === null && $owner === null && $port === null) {
            $tokenList->expectedAnyKeyword(Keyword::HOST, Keyword::DATABASE, Keyword::USER, Keyword::PASSWORD, Keyword::SOCKET, Keyword::OWNER, Keyword::PORT);
        }

        $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        $tokenList->expectEnd();

        return new AlterServerCommand($name, $host, $schema, $user, $password, $socket, $owner, $port);
    }

    /**
     * CREATE SERVER server_name
     *     FOREIGN DATA WRAPPER wrapper_name
     *     OPTIONS (option [, option] ...)
     *
     * option:
     *     HOST character-literal
     *   | DATABASE character-literal
     *   | USER character-literal
     *   | PASSWORD character-literal
     *   | SOCKET character-literal
     *   | OWNER character-literal
     *   | PORT numeric-literal
     */
    public function parseCreateServer(TokenList $tokenList): CreateServerCommand
    {
        $tokenList->consumeKeywords(Keyword::CREATE, Keyword::SERVER);
        $name = $tokenList->consumeName();
        $tokenList->consumeKeywords(Keyword::FOREIGN, Keyword::DATA, Keyword::WRAPPER);
        $wrapper = $tokenList->consumeNameOrString();

        $tokenList->consumeKeyword(Keyword::OPTIONS);
        $tokenList->consume(TokenType::LEFT_PARENTHESIS);
        $host = $schema = $user = $password = $socket = $owner = $port = null;
        do {
            if ($tokenList->mayConsumeKeyword(Keyword::HOST)) {
                $host = $tokenList->consumeString();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::DATABASE)) {
                $schema = $tokenList->consumeString();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::USER)) {
                $user = $tokenList->consumeString();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::PASSWORD)) {
                $password = $tokenList->consumeString();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::SOCKET)) {
                $socket = $tokenList->consumeString();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::OWNER)) {
                $owner = $tokenList->consumeString();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::PORT)) {
                $port = $tokenList->consumeInt();
            }
        } while ($tokenList->mayConsumeComma());
        if ($host === null && $schema === null && $user === null && $password === null && $socket === null && $owner === null && $port === null) {
            $tokenList->expectedAnyKeyword(Keyword::HOST, Keyword::DATABASE, Keyword::USER, Keyword::PASSWORD, Keyword::SOCKET, Keyword::OWNER, Keyword::PORT);
        }
        $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        $tokenList->expectEnd();

        return new CreateServerCommand($name, $wrapper, $host, $schema, $user, $password, $socket, $owner, $port);
    }

    /**
     * DROP SERVER [ IF EXISTS ] server_name
     */
    public function parseDropServer(TokenList $tokenList): DropServerCommand
    {
        $tokenList->consumeKeywords(Keyword::DROP, Keyword::SERVER);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);
        $name = $tokenList->consumeName();
        $tokenList->expectEnd();

        return new DropServerCommand($name, $ifExists);
    }

}
